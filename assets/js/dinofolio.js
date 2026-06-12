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
			var items = block.querySelectorAll( '.dinofolio-item' );

			items.forEach( function ( item ) {
				var show = false;

				if ( '*' === selector ) {
					show = true;
				} else {
					var className = selector.charAt( 0 ) === '.' ? selector.slice( 1 ) : selector;
					show = item.classList.contains( className );
				}

				item.classList.toggle( 'dinofolio-filter-hidden', ! show );
				item.setAttribute( 'aria-hidden', show ? 'false' : 'true' );
			} );

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
