/**
 * DinoFolio listing: Isotope filter/layout and image parallax.
 */
( function () {
	'use strict';

	var initializedBlocks = new WeakSet();
	var parallaxBlocks = [];
	var parallaxTicking = false;
	var parallaxListenersBound = false;
	var elementorListingsBound = false;

	function prefersReducedMotion() {
		return window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	function isElementorPreviewContext() {
		return (
			document.body.classList.contains( 'elementor-page' ) ||
			/elementor-preview=/.test( window.location.search )
		);
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

		var activeLink = filterBar.querySelector( 'li.dinofolio-current-cat a[data-filter]' );

		return activeLink ? activeLink.getAttribute( 'data-filter' ) || '*' : '*';
	}

	function itemMatchesFilter( item, filterValue ) {
		if ( '*' === filterValue ) {
			return true;
		}

		var className = filterValue.charAt( 0 ) === '.' ? filterValue.slice( 1 ) : filterValue;

		return item.classList.contains( className );
	}

	function applyCssFilter( block, filterValue ) {
		block.querySelectorAll( '.dinofolio-items-list .dinofolio-item' ).forEach( function ( item ) {
			var show = itemMatchesFilter( item, filterValue );

			item.classList.toggle( 'dinofolio-filter-hidden', ! show );
			item.setAttribute( 'aria-hidden', show ? 'false' : 'true' );
		} );
	}

	function countCategoryItems( block ) {
		var counts = { __all__: 0 };

		block.querySelectorAll( '.dinofolio-items-list .dinofolio-item' ).forEach( function ( item ) {
			counts.__all__ += 1;

			Array.prototype.forEach.call( item.classList, function ( className ) {
				if ( 0 === className.indexOf( 'dinofolio-cat-' ) ) {
					var slug = className.slice( 'dinofolio-cat-'.length );
					counts[ slug ] = ( counts[ slug ] || 0 ) + 1;
				}
			} );
		} );

		return counts;
	}

	function updateFilterCounts( block ) {
		var filterBar = block.querySelector( '.dinofolio-filter' );

		if ( ! filterBar || ! filterBar.classList.contains( 'dinofolio-show-filter-count' ) ) {
			return;
		}

		var counts = countCategoryItems( block );

		filterBar.querySelectorAll( 'a[data-filter]' ).forEach( function ( link ) {
			var countEl = link.querySelector( '.dinofolio-filter-count' );

			if ( ! countEl ) {
				return;
			}

			var slug = getFilterSlug( link.getAttribute( 'data-filter' ) || '*' );
			var count = '__all__' === slug ? counts.__all__ : counts[ slug ] || 0;

			countEl.textContent = String( count );
		} );
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

		li.appendChild( link );

		return li;
	}

	function mergeFilterTerms( block, config, filterTerms ) {
		if ( ! config.filter || ! Array.isArray( filterTerms ) || ! filterTerms.length ) {
			return;
		}

		var filterBar = block.querySelector( '.dinofolio-filter' );
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

		var filterBar = block.querySelector( '.dinofolio-filter' );
		var filterValue = getActiveFilterValue( filterBar );

		if ( block.dinofolioIsotope ) {
			block.dinofolioIsotope.arrange( { filter: filterValue } );
			block.dinofolioIsotope.layout();
			return;
		}

		applyCssFilter( block, filterValue );
	}

	function initCssFilter( block ) {
		var filterBar = block.querySelector( '.dinofolio-filter' );

		if ( ! filterBar ) {
			return;
		}

		filterBar.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a[data-filter]' );

			if ( ! link || ! filterBar.contains( link ) ) {
				return;
			}

			event.preventDefault();

			var selector = link.getAttribute( 'data-filter' ) || '*';

			applyCssFilter( block, selector );
			setActiveFilter( filterBar, link );
		} );
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
			var filterBar = block.querySelector( '.dinofolio-filter' );

			if ( filterBar ) {
				filterBar.addEventListener( 'click', function ( event ) {
					var link = event.target.closest( 'a[data-filter]' );

					if ( ! link || ! filterBar.contains( link ) ) {
						return;
					}

					event.preventDefault();

					var filterValue = link.getAttribute( 'data-filter' ) || '*';

					isotope.arrange( { filter: filterValue } );
					isotope.layout();
					setActiveFilter( filterBar, link );
				} );
			}
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

	function initListingBlock( block ) {
		if ( initializedBlocks.has( block ) ) {
			return;
		}

		initializedBlocks.add( block );

		var config = parseConfig( block );

		if ( config.isotope ) {
			initIsotope( block, config );
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
		scheduleLoadMoreInViewCheck( block, config, wrap );
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

	function bootListings( root ) {
		if ( isEditorContext() ) {
			return;
		}

		var scope = root || document;

		scope.querySelectorAll( '.dinofolio[data-dinofolio-config]' ).forEach( initListingBlock );
		bindParallaxListeners();
	}

	function bindElementorListings() {
		if ( elementorListingsBound || ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}

		elementorListingsBound = true;

		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/dinofolio-portfolio.default',
			function ( $scope ) {
				var root = $scope && $scope[ 0 ];

				if ( ! root ) {
					return;
				}

				bootListings( root );
			}
		);
	}

	function scheduleElementorBind() {
		if ( window.elementorFrontend ) {
			bindElementorListings();
			return;
		}

		window.addEventListener( 'elementor/frontend/init', bindElementorListings );

		if ( window.jQuery ) {
			window.jQuery( window ).on( 'elementor/frontend/init', bindElementorListings );
		}
	}

	function initListings() {
		bootListings( document );
	}

	scheduleElementorBind();

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initListings );
	} else {
		initListings();
	}
} )();
