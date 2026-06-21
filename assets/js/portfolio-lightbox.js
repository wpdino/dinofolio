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
		return '.dinofolio.dinofolio-lightbox-enabled a.glightbox.dinofolio-lightbox-link';
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
			autoplayVideos: true,
			closeButton: true,
			closeOnOutsideClick: true,
			escKey: true,
			keyboardNavigation: true,
			preload: true,
			skin: 'clean',
			openEffect: 'fade',
			closeEffect: 'fade',
			plyr: {
				css: false,
				js: false,
				config: {
					muted: false,
					hideControls: true,
					youtube: {
						noCookie: true,
						rel: 0,
						showinfo: 0,
						iv_load_policy: 3,
					},
				},
			},
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
