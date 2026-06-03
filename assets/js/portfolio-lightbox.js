/**
 * Portfolio listing GLightbox (bundled in assets/vendor/glightbox).
 */
( function () {
	'use strict';

	var initialized = false;

	function isEditorContext() {
		return (
			document.body.classList.contains( 'block-editor-page' ) ||
			document.body.classList.contains( 'elementor-editor-active' ) ||
			document.body.classList.contains( 'wp-customizer' )
		);
	}

	function getLightboxSelector() {
		return '.wpdino-blocks_portfolio-block.lightbox-enabled a.glightbox.portfolio-lightbox-link';
	}

	function initPortfolioLightboxes() {
		if ( initialized || isEditorContext() || typeof GLightbox === 'undefined' ) {
			return;
		}

		var links = document.querySelectorAll( getLightboxSelector() );

		if ( ! links.length ) {
			return;
		}

		initialized = true;

		GLightbox( {
			selector: getLightboxSelector(),
			touchNavigation: true,
			loop: true,
			autoplayVideos: false,
			closeButton: true,
			closeOnOutsideClick: true,
			escKey: true,
			keyboardNavigation: true,
			preload: true,
			skin: 'clean',
			openEffect: 'fade',
			closeEffect: 'fade',
		} );
	}

	function runInit() {
		initPortfolioLightboxes();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', runInit );
	} else {
		runInit();
	}

	window.addEventListener( 'load', runInit );
} )();
