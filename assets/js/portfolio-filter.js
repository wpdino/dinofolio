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

	function itemMatchesFilter( item, selector ) {
		if ( ! selector || '*' === selector ) {
			return true;
		}

		var className = selector.charAt( 0 ) === '.' ? selector.slice( 1 ) : selector;

		return item.classList.contains( className );
	}

	function applyFilter( block ) {
		var selectors = getFilterGroups( block ).map( getGroupFilterValue );
		var items = block.querySelectorAll( '.dinofolio-item' );

		items.forEach( function ( item ) {
			var show = selectors.every( function ( selector ) {
				return itemMatchesFilter( item, selector );
			} );

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
				var wrap = block.querySelector( '.dinofolio-filter-categories' ) || block.querySelector( '.dinofolio-filter' );

				if ( ! wrap ) {
					return;
				}

				block.setAttribute( 'data-dinofolio-filter-init', '1' );

				wrap.addEventListener( 'click', function ( event ) {
					var link = event.target.closest( 'a[data-filter]' );

					if ( ! link || ! wrap.contains( link ) ) {
						return;
					}

					event.preventDefault();

					var group = link.closest( '.dinofolio-filter' ) || wrap;

					setActiveFilter( group, link );
					applyFilter( block );
				} );

				wrap.addEventListener( 'change', function ( event ) {
					if ( ! event.target.closest( 'select.dinofolio-filter-select' ) ) {
						return;
					}

					applyFilter( block );
				} );
			} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initPortfolioFilters );
	} else {
		initPortfolioFilters();
	}
} )();
