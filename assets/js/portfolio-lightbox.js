/**
 * Portfolio listing GLightbox (bundled in assets/vendor/glightbox).
 */
( function () {
	'use strict';

	var lightboxConfig = {
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
	};

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

	function hasLightboxLinks() {
		return document.querySelectorAll( getLightboxSelector() ).length > 0;
	}

	function destroyPortfolioLightbox() {
		if (
			window.dinofolioLightbox &&
			typeof window.dinofolioLightbox.destroy === 'function'
		) {
			window.dinofolioLightbox.destroy();
		}

		window.dinofolioLightbox = null;
	}

	function initPortfolioLightboxes() {
		if ( isEditorContext() || typeof GLightbox === 'undefined' ) {
			return;
		}

		if ( ! hasLightboxLinks() ) {
			destroyPortfolioLightbox();
			return;
		}

		destroyPortfolioLightbox();

		window.dinofolioLightbox = GLightbox(
			Object.assign( {}, lightboxConfig, {
				selector: getLightboxSelector(),
			} )
		);
	}

	function refreshPortfolioLightbox( root ) {
		if ( isEditorContext() || typeof GLightbox === 'undefined' ) {
			return;
		}

		var scope = root || document;
		var selector = getLightboxSelector();
		var hasLinksInScope = scope.querySelectorAll( selector ).length > 0;

		if ( ! hasLinksInScope && scope === document ) {
			destroyPortfolioLightbox();
			return;
		}

		if ( ! hasLinksInScope ) {
			return;
		}

		initPortfolioLightboxes();
	}

	window.dinofolioRefreshLightbox = refreshPortfolioLightbox;

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
