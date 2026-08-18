/**
 * DinoFolio listing: Isotope filter/layout and image parallax.
 */
( function () {
	'use strict';

	var parallaxBlocks = [];
	var parallaxTicking = false;
	var parallaxListenersBound = false;
	var elementorListingsBound = false;
	var loadMoreUserHasScrolled = false;
	var loadMoreUserScrollListenerBound = false;
	var loadMoreScrollCheckTicking = false;

	var ELEMENTOR_PORTFOLIO_WIDGET_TYPES = {
		'dinofolio-portfolio.default': true,
		'dinofolio-portfolio-listing.default': true,
	};

	function prefersReducedMotion() {
		return window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	function isElementorPreviewContext() {
		if (
			document.body.classList.contains( 'elementor-page' ) ||
			/elementor-preview=/.test( window.location.search )
		) {
			return true;
		}

		// Elementor live preview iframe runs frontend scripts in edit mode.
		if (
			window.elementorFrontend &&
			typeof window.elementorFrontend.isEditMode === 'function' &&
			window.elementorFrontend.isEditMode()
		) {
			return true;
		}

		return false;
	}

	function isEditorContext() {
		if (
			document.body.classList.contains( 'block-editor-page' ) ||
			document.body.classList.contains( 'wp-customizer' )
		) {
			return true;
		}

		// Elementor adds elementor-editor-active to the preview iframe too; still init listings there.
		if ( document.body.classList.contains( 'elementor-editor-active' ) ) {
			return ! isElementorPreviewContext();
		}

		return false;
	}

	function parseConfig( block ) {
		var raw = block.getAttribute( 'data-dinofolio-config' );

		if ( ! raw ) {
			return {};
		}

		try {
			return JSON.parse( raw );
		} catch ( error ) {
			return {};
		}
	}

	function getListingGap( block, config ) {
		if ( config && ! isNaN( config.gap ) ) {
			return config.gap;
		}

		var styles = window.getComputedStyle( block );
		var gap = parseFloat( styles.getPropertyValue( '--dinofolio-gap' ) );

		if ( ! isNaN( gap ) ) {
			return gap;
		}

		var list = block.querySelector( '.dinofolio-items-list' );

		if ( ! list ) {
			return 24;
		}

		var listStyles = window.getComputedStyle( list );
		var listGap = parseFloat( listStyles.gap || listStyles.columnGap );

		return ! isNaN( listGap ) ? listGap : 24;
	}

	function getResponsiveColumns( block, columns ) {
		var activeColumns = columns || 3;
		var styles = window.getComputedStyle( block );
		var cssColumns = parseInt( styles.getPropertyValue( '--dinofolio-columns' ), 10 );

		if ( ! isNaN( cssColumns ) && cssColumns > 0 ) {
			activeColumns = cssColumns;
		}

		if ( window.matchMedia( '(max-width: 480px)' ).matches ) {
			return 1;
		}

		if ( window.matchMedia( '(max-width: 767px)' ).matches ) {
			return Math.min( 2, activeColumns );
		}

		if ( window.matchMedia( '(max-width: 1024px)' ).matches && activeColumns === 4 ) {
			return 3;
		}

		return activeColumns;
	}

	function setIsotopeItemWidths( block, config ) {
		var list = block.querySelector( '.dinofolio-items-list' );

		if ( ! list ) {
			return;
		}

		var columns = getResponsiveColumns( block, config && config.columns ? config.columns : 3 );
		var gap = getListingGap( block, config );
		var listWidth = list.getBoundingClientRect().width;

		if ( listWidth <= 0 || columns < 1 ) {
			return;
		}

		var totalGutter = gap * ( columns - 1 );
		var itemWidth = Math.floor( ( ( listWidth - totalGutter ) / columns ) * 100 ) / 100;
		var items = block.querySelectorAll( '.dinofolio-item' );

		items.forEach( function ( item ) {
			item.style.width = itemWidth + 'px';
		} );
	}

	function setActiveFilter( filterBar, link ) {
		filterBar.querySelectorAll( 'li' ).forEach( function ( li ) {
			li.classList.remove( 'dinofolio-current-cat' );
		} );

		var activeLi = link.closest( 'li' );

		if ( activeLi ) {
			activeLi.classList.add( 'dinofolio-current-cat' );
		}
	}

	function getFilterGroups( block ) {
		var wrap = block.querySelector( '.dinofolio-filter-categories' );

		if ( wrap ) {
			var groups = wrap.querySelectorAll( '.dinofolio-filter' );

			if ( groups.length ) {
				return Array.prototype.slice.call( groups );
			}
		}

		var single = block.querySelector( '.dinofolio-filter' );

		return single ? [ single ] : [];
	}

	function getGroupFilterValue( group ) {
		var select = group.querySelector( 'select.dinofolio-filter-select' );

		if ( select ) {
			return select.value || '*';
		}

		var activeLink = group.querySelector( 'li.dinofolio-current-cat a[data-filter]' );

		return activeLink ? activeLink.getAttribute( 'data-filter' ) || '*' : '*';
	}

	function getActiveFilterValues( block ) {
		return getFilterGroups( block ).map( getGroupFilterValue );
	}

	function getFilterSlug( filterValue ) {
		if ( ! filterValue || '*' === filterValue ) {
			return '__all__';
		}

		var className = filterValue.charAt( 0 ) === '.' ? filterValue.slice( 1 ) : filterValue;

		return className.replace( 'dinofolio-cat-', '' );
	}

	function getActiveFilterValue( filterBar ) {
		if ( ! filterBar ) {
			return '*';
		}

		return getGroupFilterValue( filterBar );
	}

	function itemMatchesFilter( item, filterValue ) {
		if ( ! filterValue || '*' === filterValue ) {
			return true;
		}

		var className = filterValue.charAt( 0 ) === '.' ? filterValue.slice( 1 ) : filterValue;

		return item.classList.contains( className );
	}

	function itemMatchesFilters( item, selectors ) {
		if ( ! selectors || ! selectors.length ) {
			return true;
		}

		return selectors.every( function ( selector ) {
			return itemMatchesFilter( item, selector );
		} );
	}

	function countVisibleListingItems( block ) {
		var list = block.querySelector( '.dinofolio-items-list, .dinofolio-pro-items-list' );

		if ( ! list ) {
			return 0;
		}

		var count = 0;

		list.querySelectorAll( '.dinofolio-item' ).forEach( function ( item ) {
			if ( ! item.classList.contains( 'dinofolio-filter-hidden' ) ) {
				count += 1;
			}
		} );

		return count;
	}

	function updateFilterEmptyState( block ) {
		if ( ! block.classList.contains( 'dinofolio-has-category-filter' ) ) {
			return;
		}

		var notice = block.querySelector( '.dinofolio-filter-empty' );
		var isEmpty = countVisibleListingItems( block ) === 0;

		block.classList.toggle( 'dinofolio-has-filter-empty', isEmpty );

		if ( notice ) {
			notice.hidden = ! isEmpty;
		}
	}

	function applyCssFilter( block, filterValue ) {
		var selectors = Array.isArray( filterValue ) ? filterValue : [ filterValue || '*' ];

		block.querySelectorAll( '.dinofolio-items-list .dinofolio-item' ).forEach( function ( item ) {
			var show = itemMatchesFilters( item, selectors );

			item.classList.toggle( 'dinofolio-filter-hidden', ! show );
			item.setAttribute( 'aria-hidden', show ? 'false' : 'true' );
		} );
	}

	function applyListingFilter( block ) {
		var selectors = getActiveFilterValues( block );

		applyCssFilter( block, selectors );

		if ( block.dinofolioIsotope ) {
			block.dinofolioIsotope.arrange( {
				filter: function ( itemElem ) {
					return itemElem && ! itemElem.classList.contains( 'dinofolio-filter-hidden' );
				},
			} );
			block.dinofolioIsotope.layout();
		}

		if ( updateFilterCounts( block ) ) {
			selectors = getActiveFilterValues( block );
			applyCssFilter( block, selectors );

			if ( block.dinofolioIsotope ) {
				block.dinofolioIsotope.arrange( {
					filter: function ( itemElem ) {
						return itemElem && ! itemElem.classList.contains( 'dinofolio-filter-hidden' );
					},
				} );
				block.dinofolioIsotope.layout();
			}
		}

		updateFilterEmptyState( block );

		if ( typeof block.dispatchEvent === 'function' ) {
			block.dispatchEvent(
				new CustomEvent( 'dinofolio:filter', {
					bubbles: true,
				} )
			);
		}
	}

	function countCategoryItems( block ) {
		var counts = { __all__: 0 };

		block.querySelectorAll( '.dinofolio-items-list .dinofolio-item' ).forEach( function ( item ) {
			var hasCategory = false;

			Array.prototype.forEach.call( item.classList, function ( className ) {
				if ( 0 === className.indexOf( 'dinofolio-cat-' ) ) {
					hasCategory = true;
					var slug = className.slice( 'dinofolio-cat-'.length );
					counts[ slug ] = ( counts[ slug ] || 0 ) + 1;
				}
			} );

			if ( hasCategory ) {
				counts.__all__ += 1;
			}
		} );

		return counts;
	}

	function getGroupTermSelectors( group ) {
		var selectors = [];

		group.querySelectorAll( 'a[data-filter]' ).forEach( function ( link ) {
			var selector = link.getAttribute( 'data-filter' ) || '*';

			if ( selector && '*' !== selector ) {
				selectors.push( selector );
			}
		} );

		group.querySelectorAll( 'select.dinofolio-filter-select option' ).forEach( function ( option ) {
			var selector = option.value || '*';

			if ( selector && '*' !== selector ) {
				selectors.push( selector );
			}
		} );

		return selectors;
	}

	function getOtherGroupSelectors( block, currentGroup ) {
		return getFilterGroups( block )
			.filter( function ( group ) {
				return group !== currentGroup;
			} )
			.map( getGroupFilterValue )
			.filter( function ( selector ) {
				return selector && '*' !== selector;
			} );
	}

	function setFilterLinkDisabled( link, isDisabled ) {
		var li = link.closest( 'li' );

		if ( isDisabled ) {
			link.classList.add( 'dinofolio-filter-link--disabled' );
			link.setAttribute( 'aria-disabled', 'true' );
			link.setAttribute( 'tabindex', '-1' );

			if ( li ) {
				li.classList.add( 'is-disabled' );
			}
		} else {
			link.classList.remove( 'dinofolio-filter-link--disabled' );
			link.removeAttribute( 'aria-disabled' );
			link.removeAttribute( 'tabindex' );

			if ( li ) {
				li.classList.remove( 'is-disabled' );
			}
		}
	}

	function updateFilterCounts( block ) {
		var activeChanged = false;

		getFilterGroups( block ).forEach( function ( filterBar ) {
			if ( ! filterBar.classList.contains( 'dinofolio-show-filter-count' ) ) {
				return;
			}

			var groupSelectors = getGroupTermSelectors( filterBar );
			var otherSelectors = getOtherGroupSelectors( block, filterBar );

			filterBar.querySelectorAll( 'a[data-filter]' ).forEach( function ( link ) {
				var countEl = link.querySelector( '.dinofolio-filter-count' );

				if ( ! countEl ) {
					return;
				}

				var selector = link.getAttribute( 'data-filter' ) || '*';
				var count = 0;

				block.querySelectorAll( '.dinofolio-items-list .dinofolio-item' ).forEach( function ( item ) {
					if ( ! itemMatchesFilters( item, otherSelectors ) ) {
						return;
					}

					var matches = ( '*' === selector )
						? groupSelectors.some( function ( termSelector ) {
							return itemMatchesFilter( item, termSelector );
						} )
						: itemMatchesFilter( item, selector );

					if ( matches ) {
						count += 1;
					}
				} );

				countEl.textContent = String( count );
				setFilterLinkDisabled( link, count <= 0 );
			} );

			var activeLink = filterBar.querySelector( 'li.dinofolio-current-cat a[data-filter]' );

			if ( activeLink && activeLink.classList.contains( 'dinofolio-filter-link--disabled' ) ) {
				var allLink = filterBar.querySelector( 'a[data-filter="*"]' );

				if ( allLink && ! allLink.classList.contains( 'dinofolio-filter-link--disabled' ) ) {
					setActiveFilter( filterBar, allLink );
					activeChanged = true;
				}
			}
		} );

		return activeChanged;
	}

	function getExistingFilterSlugs( filterBar ) {
		var slugs = {};

		filterBar.querySelectorAll( 'a[data-filter]' ).forEach( function ( link ) {
			var slug = getFilterSlug( link.getAttribute( 'data-filter' ) || '*' );

			if ( '__all__' !== slug ) {
				slugs[ slug ] = true;
			}
		} );

		return slugs;
	}

	function createFilterTab( term, showCount, count ) {
		var li = document.createElement( 'li' );
		var link = document.createElement( 'a' );
		var label = document.createElement( 'span' );

		li.setAttribute( 'role', 'listitem' );
		link.href = '#';
		link.className = 'dinofolio-filter-link';
		link.setAttribute( 'data-filter', term.filter || '.dinofolio-cat-' + term.slug );
		label.className = 'dinofolio-filter-label';
		label.textContent = term.name;
		link.appendChild( label );

		if ( showCount ) {
			var countEl = document.createElement( 'span' );
			countEl.className = 'dinofolio-filter-count';
			countEl.textContent = String( count || 0 );
			link.appendChild( countEl );
		}

		if ( showCount && ( count || 0 ) <= 0 ) {
			setFilterLinkDisabled( link, true );
		}

		li.appendChild( link );

		return li;
	}

	function mergeFilterTerms( block, config, filterTerms ) {
		if ( ! config.filter || ! Array.isArray( filterTerms ) || ! filterTerms.length ) {
			return;
		}

		var filterBar = block.querySelector( '.dinofolio-filter[data-filter-group="category"]' ) || block.querySelector( '.dinofolio-filter' );
		var list = filterBar ? filterBar.querySelector( 'ul' ) : null;

		if ( ! list ) {
			return;
		}

		var existing = getExistingFilterSlugs( filterBar );
		var showCount = !! config.showFilterCount;
		var counts = showCount ? countCategoryItems( block ) : {};

		filterTerms.forEach( function ( term ) {
			if ( ! term || ! term.slug || existing[ term.slug ] ) {
				return;
			}

			existing[ term.slug ] = true;
			list.appendChild(
				createFilterTab( term, showCount, showCount ? counts[ term.slug ] || 0 : 0 )
			);
		} );
	}

	function refreshListingFilters( block, config, filterTerms ) {
		if ( ! config.filter ) {
			return;
		}

		mergeFilterTerms( block, config, filterTerms );
		updateFilterCounts( block );
		applyListingFilter( block );
	}

	function bindListingFilter( block ) {
		var wrap = block.querySelector( '.dinofolio-filter-categories' ) || block.querySelector( '.dinofolio-filter' );

		if ( ! wrap || wrap.dataset.dinofolioFilterBound === '1' ) {
			return;
		}

		wrap.dataset.dinofolioFilterBound = '1';

		wrap.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a[data-filter]' );

			if ( ! link || ! wrap.contains( link ) || link.classList.contains( 'dinofolio-filter-link--disabled' ) ) {
				return;
			}

			event.preventDefault();

			var group = link.closest( '.dinofolio-filter' ) || wrap;

			setActiveFilter( group, link );
			applyListingFilter( block );
		} );

		wrap.addEventListener( 'change', function ( event ) {
			if ( ! event.target.closest( 'select.dinofolio-filter-select' ) ) {
				return;
			}

			applyListingFilter( block );
		} );
	}

	function initCssFilter( block ) {
		bindListingFilter( block );
		updateFilterEmptyState( block );
	}

	function initIsotope( block, config ) {
		if ( typeof window.Isotope !== 'function' || typeof window.imagesLoaded !== 'function' ) {
			if ( config.filter ) {
				initCssFilter( block );
			}
			return null;
		}

		var list = block.querySelector( '.dinofolio-items-list' );

		if ( ! list ) {
			return null;
		}

		var layoutMode = 'masonry' === config.layout ? 'masonry' : 'fitRows';
		var gap = getListingGap( block, config );

		setIsotopeItemWidths( block, config );

		var isotopeOptions = {
			itemSelector: '.dinofolio-item',
			layoutMode: layoutMode,
			percentPosition: true,
			transitionDuration: prefersReducedMotion() ? '0s' : '0.65s',
			hiddenStyle: {
				opacity: 0,
				transform: 'scale(0.92) translateY(16px)',
			},
			visibleStyle: {
				opacity: 1,
				transform: 'scale(1) translateY(0)',
			},
			filter: config.filter ? '*' : undefined,
		};

		if ( 'masonry' === layoutMode ) {
			isotopeOptions.masonry = {
				columnWidth: '.dinofolio-item',
				gutter: gap,
			};
		} else {
			isotopeOptions.fitRows = {
				gutter: gap,
			};
		}

		var isotope = new window.Isotope( list, isotopeOptions );

		window.imagesLoaded( list, function () {
			setIsotopeItemWidths( block, config );
			isotope.layout();
		} );

		list.addEventListener( 'load', function ( event ) {
			if ( event.target && event.target.classList && event.target.classList.contains( 'dinofolio-item-image' ) ) {
				setIsotopeItemWidths( block, config );
				isotope.layout();
			}
		}, true );

		if ( config.filter ) {
			bindListingFilter( block );
		}

		var resizeTimer;

		window.addEventListener( 'resize', function () {
			window.clearTimeout( resizeTimer );
			resizeTimer = window.setTimeout( function () {
				setIsotopeItemWidths( block, config );
				isotope.layout();
			}, 150 );
		} );

		block.dinofolioIsotope = isotope;

		if ( list.getBoundingClientRect().width <= 0 ) {
			window.requestAnimationFrame( function () {
				setIsotopeItemWidths( block, config );
				isotope.layout();
			} );
		}

		return isotope;
	}

	function getParallaxScale( containerHeight, maxOffset ) {
		if ( containerHeight <= 0 ) {
			return 1.1;
		}

		// Scale must cover vertical translation so clipped edges never show the container background.
		return 1 + ( maxOffset * 2 ) / containerHeight + 0.015;
	}

	function updateParallaxBlock( block ) {
		var intensity = 0.045;
		var maxOffset = 12;
		var targets = block.querySelectorAll( '.dinofolio-parallax-target' );

		targets.forEach( function ( target ) {
			var thumb = target.closest( '.dinofolio-item-thumbnail, .dinofolio-overlay-card' );

			if ( ! thumb ) {
				return;
			}

			var rect = thumb.getBoundingClientRect();
			var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
			var elementCenter = rect.top + rect.height / 2;
			var distance = elementCenter - viewportHeight / 2;
			var offset = Math.max( Math.min( distance * intensity, maxOffset ), -maxOffset );
			var scale = getParallaxScale( rect.height, maxOffset );

			target.style.transform = 'translate3d(0, ' + offset + 'px, 0) scale(' + scale + ')';
		} );
	}

	function initParallax( block ) {
		if ( prefersReducedMotion() ) {
			return;
		}

		if ( ! block.querySelector( '.dinofolio-parallax-target' ) ) {
			return;
		}

		parallaxBlocks.push( block );
		updateParallaxBlock( block );

		block.querySelectorAll( '.dinofolio-parallax-target' ).forEach( function ( target ) {
			if ( target.complete ) {
				return;
			}

			target.addEventListener(
				'load',
				function () {
					updateParallaxBlock( block );
				},
				{ once: true }
			);
		} );
	}

	function onParallaxScroll() {
		if ( parallaxTicking || ! parallaxBlocks.length ) {
			return;
		}

		parallaxTicking = true;

		window.requestAnimationFrame( function () {
			parallaxBlocks.forEach( updateParallaxBlock );
			parallaxTicking = false;
		} );
	}

	function refreshListingIsotopeLayout( carousel ) {
		if ( ! carousel ) {
			return;
		}

		var block = carousel.closest( '.dinofolio[data-dinofolio-config]' );

		if ( block && block.dinofolioIsotope && typeof block.dinofolioIsotope.layout === 'function' ) {
			block.dinofolioIsotope.layout();
		}
	}

	function initHorizontalItemGalleryCarousel( carousel ) {
		var viewport = carousel.querySelector( '.dinofolio-item-gallery-carousel-viewport' );
		var track = carousel.querySelector( '.dinofolio-item-gallery-carousel-track' );
		var prevButton = carousel.querySelector( '.dinofolio-carousel-prev' );
		var nextButton = carousel.querySelector( '.dinofolio-carousel-next' );

		if ( ! viewport || ! track || ! prevButton || ! nextButton ) {
			return;
		}

		var usesContainerSlides = typeof CSS !== 'undefined' && typeof CSS.supports === 'function' && CSS.supports( 'width', '1cqw' );

		var resetToFirstSlide = function () {
			viewport.scrollTo( {
				left: 0,
				behavior: 'auto',
			} );
		};

		var updateSlideWidths = function () {
			if ( usesContainerSlides ) {
				return;
			}

			var slideWidth = Math.floor( viewport.getBoundingClientRect().width );

			if ( slideWidth < 1 ) {
				return;
			}

			track.querySelectorAll( '.dinofolio-item-gallery-slide' ).forEach( function ( slide ) {
				slide.style.flexBasis = slideWidth + 'px';
				slide.style.width = slideWidth + 'px';
				slide.style.maxWidth = slideWidth + 'px';
				slide.style.minWidth = slideWidth + 'px';
			} );
		};

		var getSlideCount = function () {
			return track.querySelectorAll( '.dinofolio-item-gallery-slide' ).length;
		};

		var getScrollStep = function () {
			return Math.max( 1, viewport.clientWidth );
		};

		var getMaxScroll = function () {
			return Math.max( 0, viewport.scrollWidth - viewport.clientWidth );
		};

		var canLoop = function () {
			return getSlideCount() > 1;
		};

		var isAtStart = function () {
			return viewport.scrollLeft <= 1;
		};

		var isAtEnd = function () {
			return viewport.scrollLeft >= getMaxScroll() - 1;
		};

		var updateNavState = function () {
			if ( canLoop() ) {
				prevButton.disabled = false;
				nextButton.disabled = false;
				return;
			}

			prevButton.disabled = isAtStart();
			nextButton.disabled = isAtEnd();
		};

		var scrollByStep = function ( direction ) {
			if ( ! canLoop() ) {
				viewport.scrollBy( {
					left: direction * getScrollStep(),
					behavior: 'smooth',
				} );
				return;
			}

			if ( direction > 0 && isAtEnd() ) {
				viewport.scrollTo( {
					left: 0,
					behavior: 'smooth',
				} );
				return;
			}

			if ( direction < 0 && isAtStart() ) {
				viewport.scrollTo( {
					left: getMaxScroll(),
					behavior: 'smooth',
				} );
				return;
			}

			viewport.scrollBy( {
				left: direction * getScrollStep(),
				behavior: 'smooth',
			} );
		};

		prevButton.addEventListener( 'click', function () {
			scrollByStep( -1 );
		} );

		nextButton.addEventListener( 'click', function () {
			scrollByStep( 1 );
		} );

		viewport.addEventListener( 'scroll', updateNavState, { passive: true } );
		window.addEventListener( 'resize', function () {
			updateSlideWidths();
			updateNavState();
		} );

		carousel.querySelectorAll( 'img' ).forEach( function ( image ) {
			if ( image.complete ) {
				return;
			}

			image.addEventListener(
				'load',
				function () {
					updateSlideWidths();

					if ( viewport.scrollLeft <= 1 ) {
						resetToFirstSlide();
					}

					updateNavState();
					refreshListingIsotopeLayout( carousel );
				},
				{ once: true }
			);
		} );

		if ( typeof ResizeObserver !== 'undefined' ) {
			var resizeObserver = new ResizeObserver( function () {
				updateSlideWidths();
				updateNavState();
			} );

			resizeObserver.observe( viewport );
		}

		updateSlideWidths();
		resetToFirstSlide();
		updateNavState();

		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				updateSlideWidths();
				resetToFirstSlide();
				updateNavState();
				refreshListingIsotopeLayout( carousel );
			} );
		} );
	}

	function initItemGalleryCarousels( root ) {
		var scope = root || document;

		scope.querySelectorAll( '[data-dinofolio-item-gallery-carousel]:not([data-dinofolio-item-gallery-bound])' ).forEach( function ( carousel ) {
			initHorizontalItemGalleryCarousel( carousel );
			carousel.setAttribute( 'data-dinofolio-item-gallery-bound', '1' );
		} );
	}

	function bindGalleryCarouselObserver() {
		if ( typeof MutationObserver === 'undefined' || document.body.dataset.dinofolioGalleryObserverBound === '1' ) {
			return;
		}

		document.body.dataset.dinofolioGalleryObserverBound = '1';

		var observer = new MutationObserver( function () {
			initItemGalleryCarousels( document );
		} );

		observer.observe( document.body, {
			childList: true,
			subtree: true,
		} );
	}

	function destroyListingBlock( block ) {
		if ( ! block ) {
			return;
		}

		if ( block.dinofolioIsotope && typeof block.dinofolioIsotope.destroy === 'function' ) {
			block.dinofolioIsotope.destroy();
		}

		delete block.dinofolioIsotope;

		var loadMore = block.querySelector( '.dinofolio-load-more' );

		if ( loadMore ) {
			if ( loadMore.dinofolioLoadMoreObserver ) {
				loadMore.dinofolioLoadMoreObserver.disconnect();
				loadMore.dinofolioLoadMoreObserver = null;
			}

			delete loadMore.dataset.dinofolioLoadMoreBound;
			delete loadMore.dataset.dinofolioLoadMoreLoading;
		}

		var filterWrap = block.querySelector( '.dinofolio-filter-categories' ) || block.querySelector( '.dinofolio-filter' );

		if ( filterWrap ) {
			delete filterWrap.dataset.dinofolioFilterBound;
		}

		var parallaxIndex = parallaxBlocks.indexOf( block );

		if ( parallaxIndex !== -1 ) {
			parallaxBlocks.splice( parallaxIndex, 1 );
		}

		delete block.dataset.dinofolioListingInit;
	}

	function initListingBlock( block, options ) {
		options = options || {};

		if ( ! block || ! block.getAttribute( 'data-dinofolio-config' ) ) {
			return;
		}

		if ( block.dataset.dinofolioListingInit === '1' && ! options.force ) {
			return;
		}

		if ( options.force ) {
			destroyListingBlock( block );
		}

		var config = parseConfig( block );
		var initComplete = true;

		if ( config.isotope ) {
			var isotopeInstance = initIsotope( block, config );

			if ( ! isotopeInstance && typeof window.Isotope !== 'function' ) {
				initComplete = false;
			}
		} else if ( config.filter ) {
			initCssFilter( block );
		}

		if ( config.parallax ) {
			block.classList.add( 'dinofolio-parallax-enabled' );
			initParallax( block );
		}

		if ( config.loadMore ) {
			initLoadMore( block, config );
		}

		initItemGalleryCarousels( block );

		if ( initComplete ) {
			block.dataset.dinofolioListingInit = '1';
		}
	}

	function getListingI18n( key, fallback ) {
		if (
			window.dinofolioListing &&
			window.dinofolioListing.i18n &&
			window.dinofolioListing.i18n[ key ]
		) {
			return window.dinofolioListing.i18n[ key ];
		}

		return fallback;
	}

	function setLoadMoreLoading( wrap, isLoading ) {
		var button = wrap.querySelector( '.dinofolio-load-more-btn' );
		var preloader = wrap.querySelector( '.dinofolio-load-more-preloader' );

		if ( ! preloader ) {
			return;
		}

		if ( button ) {
			button.hidden = isLoading;
			button.disabled = isLoading;
			button.setAttribute( 'aria-busy', isLoading ? 'true' : 'false' );
		}

		preloader.hidden = ! isLoading;
		preloader.setAttribute( 'aria-hidden', isLoading ? 'false' : 'true' );
	}

	function getLoadMoreTrigger( wrap, config ) {
		if ( config && config.loadMoreTrigger ) {
			return config.loadMoreTrigger;
		}

		return wrap.getAttribute( 'data-load-more-trigger' ) || 'click';
	}

	function hasLoadMoreUserScrolled() {
		return loadMoreUserHasScrolled;
	}

	function scheduleInViewLoadMoreChecksFromUserScroll() {
		if ( loadMoreScrollCheckTicking ) {
			return;
		}

		loadMoreScrollCheckTicking = true;

		window.requestAnimationFrame( function () {
			loadMoreScrollCheckTicking = false;

			document.querySelectorAll( '.dinofolio-load-more--in-view[data-dinofolio-load-more-bound="1"]' ).forEach( function ( wrap ) {
				var block = wrap.closest( '.dinofolio[data-dinofolio-config]' );

				if ( ! block ) {
					return;
				}

				scheduleLoadMoreInViewCheck( block, parseConfig( block ), wrap );
			} );
		} );
	}

	function bindLoadMoreUserScrollListener() {
		if ( loadMoreUserScrollListenerBound ) {
			return;
		}

		loadMoreUserScrollListenerBound = true;

		function markLoadMoreUserScrolled() {
			if ( loadMoreUserHasScrolled ) {
				scheduleInViewLoadMoreChecksFromUserScroll();
				return;
			}

			loadMoreUserHasScrolled = true;
			scheduleInViewLoadMoreChecksFromUserScroll();
		}

		window.addEventListener( 'scroll', markLoadMoreUserScrolled, { passive: true } );
		window.addEventListener( 'wheel', markLoadMoreUserScrolled, { passive: true } );
		window.addEventListener( 'touchmove', markLoadMoreUserScrolled, { passive: true } );
		window.addEventListener( 'keydown', function ( event ) {
			var scrollKeys = {
				32: true,
				33: true,
				34: true,
				35: true,
				36: true,
				38: true,
				40: true,
			};

			if ( scrollKeys[ event.keyCode ] ) {
				markLoadMoreUserScrolled();
			}
		} );
	}

	function isLoadMoreSentinelInView( wrap ) {
		if ( ! wrap || ! wrap.isConnected ) {
			return false;
		}

		var rect = wrap.getBoundingClientRect();
		var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
		var margin = 200;

		return rect.top <= viewportHeight + margin && rect.bottom >= -margin;
	}

	function scheduleLoadMoreInViewCheck( block, config, wrap ) {
		if ( ! wrap || ! wrap.isConnected ) {
			return;
		}

		if ( 'in_view' !== getLoadMoreTrigger( wrap, config ) ) {
			return;
		}

		if ( ! hasLoadMoreUserScrolled() ) {
			return;
		}

		window.requestAnimationFrame( function () {
			if ( ! wrap.isConnected || wrap.dataset.dinofolioLoadMoreLoading === '1' ) {
				return;
			}

			var currentPage = parseInt( wrap.getAttribute( 'data-current-page' ), 10 ) || 1;
			var maxPages = parseInt( wrap.getAttribute( 'data-max-pages' ), 10 ) || 1;

			if ( currentPage >= maxPages ) {
				return;
			}

			if ( ! isLoadMoreSentinelInView( wrap ) ) {
				return;
			}

			requestLoadMorePage( block, config, wrap );
		} );
	}

	function requestLoadMorePage( block, config, wrap ) {
		var button = wrap.querySelector( '.dinofolio-load-more-btn' );
		var currentPage = parseInt( wrap.getAttribute( 'data-current-page' ), 10 ) || 1;

		if ( button ) {
			currentPage = parseInt( button.getAttribute( 'data-page' ), 10 ) || currentPage;
		}

		var maxPages = parseInt( wrap.getAttribute( 'data-max-pages' ), 10 ) || 1;
		var nextPage = currentPage + 1;

		if ( currentPage >= maxPages || wrap.dataset.dinofolioLoadMoreLoading === '1' ) {
			return Promise.resolve( false );
		}

		if ( ! window.dinofolioListing || ! window.dinofolioListing.ajaxUrl || ! window.dinofolioListing.nonce ) {
			return Promise.resolve( false );
		}

		wrap.dataset.dinofolioLoadMoreLoading = '1';
		setLoadMoreLoading( wrap, true );

		var formData = new FormData();
		formData.append( 'action', 'dinofolio_load_more' );
		formData.append( 'nonce', window.dinofolioListing.nonce );
		formData.append( 'page', String( nextPage ) );
		formData.append( 'attributes', JSON.stringify( config.query || {} ) );

		var galleryId = block.getAttribute( 'data-dinofolio-gallery' );
		if ( galleryId ) {
			formData.append( 'galleryId', galleryId );
		}

		return fetch( window.dinofolioListing.ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( payload ) {
				if ( ! payload || ! payload.success || ! payload.data || ! payload.data.html ) {
					throw new Error( 'invalid_response' );
				}

				appendPortfolioItems( block, config, payload.data.html, payload.data.filterTerms || [] );

				if ( button ) {
					button.setAttribute( 'data-page', String( nextPage ) );
				}

				wrap.setAttribute( 'data-current-page', String( nextPage ) );
				wrap.setAttribute( 'data-max-pages', String( payload.data.maxPages || maxPages ) );

				if ( ! payload.data.hasMore ) {
					if ( wrap.dinofolioLoadMoreObserver ) {
						wrap.dinofolioLoadMoreObserver.disconnect();
						wrap.dinofolioLoadMoreObserver = null;
					}

					wrap.remove();
					return false;
				}

				setLoadMoreLoading( wrap, false );
				return true;
			} )
			.catch( function () {
				setLoadMoreLoading( wrap, false );
				window.alert(
					getListingI18n( 'error', 'Unable to load more projects. Please try again.' )
				);
				return false;
			} )
			.finally( function () {
				delete wrap.dataset.dinofolioLoadMoreLoading;
			} );
	}

	function bindLoadMoreInView( block, config, wrap ) {
		if ( ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		if ( wrap.dinofolioLoadMoreObserver ) {
			wrap.dinofolioLoadMoreObserver.disconnect();
		}

		wrap.dinofolioLoadMoreObserver = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}

					scheduleLoadMoreInViewCheck( block, config, wrap );
				} );
			},
			{
				root: null,
				rootMargin: '0px 0px 200px 0px',
				threshold: 0,
			}
		);

		wrap.dinofolioLoadMoreObserver.observe( wrap );
		bindLoadMoreUserScrollListener();
	}

	function initLoadMore( block, config ) {
		var wrap = block.querySelector( '.dinofolio-load-more' );

		if ( ! wrap || wrap.dataset.dinofolioLoadMoreBound === '1' ) {
			return;
		}

		if ( ! window.dinofolioListing || ! window.dinofolioListing.ajaxUrl || ! window.dinofolioListing.nonce ) {
			return;
		}

		wrap.dataset.dinofolioLoadMoreBound = '1';

		var trigger = getLoadMoreTrigger( wrap, config );
		var button = wrap.querySelector( '.dinofolio-load-more-btn' );

		if ( 'in_view' === trigger ) {
			bindLoadMoreInView( block, config, wrap );
			return;
		}

		if ( ! button ) {
			return;
		}

		button.addEventListener( 'click', function () {
			requestLoadMorePage( block, config, wrap );
		} );
	}

	function appendPortfolioItems( block, config, html, filterTerms ) {
		var list = block.querySelector( '.dinofolio-items-list' );

		if ( ! list ) {
			return [];
		}

		var template = document.createElement( 'div' );
		template.innerHTML = html;
		var newItems = Array.prototype.slice.call( template.children );

		newItems.forEach( function ( item ) {
			list.appendChild( item );
		} );

		function afterItemsAppended() {
			refreshListingFilters( block, config, filterTerms );

			if ( config.parallax ) {
				newItems.forEach( function ( item ) {
					if ( item.querySelector( '.dinofolio-parallax-target' ) && parallaxBlocks.indexOf( block ) === -1 ) {
						parallaxBlocks.push( block );
					}
				} );
				updateParallaxBlock( block );
				bindParallaxListeners();
			}

			if ( typeof window.dinofolioRefreshLightbox === 'function' ) {
				window.dinofolioRefreshLightbox( block );
			}

			initItemGalleryCarousels( block );

			var loadMoreWrap = block.querySelector( '.dinofolio-load-more' );
			if ( loadMoreWrap ) {
				scheduleLoadMoreInViewCheck( block, config, loadMoreWrap );
			}
		}

		if ( block.dinofolioIsotope ) {
			setIsotopeItemWidths( block, config );

			if ( typeof window.imagesLoaded === 'function' ) {
				window.imagesLoaded( list, function () {
					setIsotopeItemWidths( block, config );
					block.dinofolioIsotope.appended( newItems );
					block.dinofolioIsotope.layout();
					afterItemsAppended();
				} );
			} else {
				block.dinofolioIsotope.appended( newItems );
				block.dinofolioIsotope.layout();
				afterItemsAppended();
			}
		} else {
			afterItemsAppended();
		}

		return newItems;
	}

	function bindParallaxListeners() {
		if ( parallaxListenersBound || ! parallaxBlocks.length ) {
			return;
		}

		parallaxListenersBound = true;

		window.addEventListener( 'scroll', onParallaxScroll, { passive: true } );
		window.addEventListener( 'resize', onParallaxScroll, { passive: true } );
	}

	function bootListings( root, options ) {
		options = options || {};

		if ( ! options.force && isEditorContext() ) {
			return;
		}

		var scope = root || document;

		scope.querySelectorAll( '.dinofolio[data-dinofolio-config]' ).forEach( function ( block ) {
			initListingBlock( block, options );
		} );
		bindParallaxListeners();
	}

	function scheduleListingInit( root, options ) {
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				bootListings( root, options );
			} );
		} );
	}

	function getElementorScopeRoot( $scope ) {
		return $scope && $scope[ 0 ] ? $scope[ 0 ] : null;
	}

	function isElementorPortfolioWidget( $scope ) {
		if ( ! $scope || typeof $scope.data !== 'function' ) {
			return false;
		}

		var widgetType = $scope.data( 'widget_type' ) || '';

		return !! ELEMENTOR_PORTFOLIO_WIDGET_TYPES[ widgetType ];
	}

	function getElementorHooks() {
		if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return null;
		}

		if ( typeof window.elementorFrontend.hooks.addAction !== 'function' ) {
			return null;
		}

		return window.elementorFrontend.hooks;
	}

	function onElementorPortfolioWidgetReady( $scope ) {
		scheduleListingInit( getElementorScopeRoot( $scope ), { force: true } );
	}

	function bindElementorListings() {
		if ( elementorListingsBound ) {
			return true;
		}

		var hooks = getElementorHooks();

		if ( ! hooks ) {
			return false;
		}

		elementorListingsBound = true;

		var widgetHooks = [
			'frontend/element_ready/dinofolio-portfolio.default',
			'frontend/element_ready/dinofolio-portfolio-listing.default',
		];

		widgetHooks.forEach( function ( hookName ) {
			hooks.addAction( hookName, onElementorPortfolioWidgetReady );
		} );

		hooks.addAction(
			'frontend/element_ready/widget',
			function ( $scope ) {
				if ( ! isElementorPortfolioWidget( $scope ) ) {
					return;
				}

				onElementorPortfolioWidgetReady( $scope );
			}
		);

		return true;
	}

	function scheduleElementorBind() {
		var retryCount = 0;
		var maxRetries = 50;

		function tryBind() {
			if ( bindElementorListings() ) {
				return;
			}

			if ( retryCount >= maxRetries ) {
				return;
			}

			retryCount += 1;
			window.setTimeout( tryBind, 100 );
		}

		function onElementorFrontendInit() {
			retryCount = 0;
			tryBind();
		}

		if ( window.jQuery ) {
			window.jQuery( window ).on( 'elementor/frontend/init', onElementorFrontendInit );
		} else {
			window.addEventListener( 'elementor/frontend/init', onElementorFrontendInit );
		}

		// Script may load after elementorFrontend exists but before hooks are attached.
		tryBind();
	}

	function initListings() {
		bootListings( document );
		initItemGalleryCarousels( document );
		bindGalleryCarouselObserver();
	}

	scheduleElementorBind();

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initListings );
	} else {
		initListings();
	}
} )();
