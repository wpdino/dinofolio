/**
 * Portfolio listing category filter — toggles classes; animations are CSS-only.
 */
( function () {
	'use strict';

	function isEditorContext() {
		return (
			document.body.classList.contains( 'block-editor-page' ) ||
			document.body.classList.contains( 'elementor-editor-active' ) ||
			document.body.classList.contains( 'wp-customizer' )
		);
	}

	function applyFilter( block, selector ) {
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

	function initPortfolioFilters() {
		if ( isEditorContext() ) {
			return;
		}

		document
			.querySelectorAll( '.dinofolio.dinofolio-has-category-filter:not([data-dinofolio-filter-init])' )
			.forEach( function ( block ) {
				var filterBar = block.querySelector( '.dinofolio-filter' );

				if ( ! filterBar ) {
					return;
				}

				block.setAttribute( 'data-dinofolio-filter-init', '1' );

				filterBar.addEventListener( 'click', function ( event ) {
					var link = event.target.closest( 'a[data-filter]' );

					if ( ! link || ! filterBar.contains( link ) ) {
						return;
					}

					event.preventDefault();

					var selector = link.getAttribute( 'data-filter' ) || '*';

					setActiveFilter( filterBar, link );
					applyFilter( block, selector );
				} );
			} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initPortfolioFilters );
	} else {
		initPortfolioFilters();
	}
} )();
