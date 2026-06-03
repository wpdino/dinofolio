/**
 * Portfolio listing category filter (show/hide items by taxonomy class).
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
		var items = block.querySelectorAll( '.wpdino-blocks_portfolio-block_item' );

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
			li.classList.remove( 'current-cat' );
		} );

		var activeLi = link.closest( 'li' );

		if ( activeLi ) {
			activeLi.classList.add( 'current-cat' );
		}
	}

	function initPortfolioFilters() {
		if ( isEditorContext() ) {
			return;
		}

		document
			.querySelectorAll( '.wpdino-blocks_portfolio-block.has-category-filter:not([data-dinofolio-filter-init])' )
			.forEach( function ( block ) {
				var filterBar = block.querySelector( '.wpdino-blocks_portfolio-block_filter' );

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
