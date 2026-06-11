(function () {
	'use strict';

	var galleryLinks = document.querySelectorAll('.dinofolio-portfolio-gallery a.glightbox');

	if (galleryLinks.length && typeof GLightbox !== 'undefined') {
		GLightbox({
			selector: '.dinofolio-portfolio-gallery a.glightbox',
			touchNavigation: true,
			loop: true,
			autoplayVideos: false,
			closeButton: true,
			closeOnOutsideClick: true,
		});
	}

	var initHorizontalCarousel = function (carousel, options) {
		options = options || {};
		var viewportSelector = options.viewportSelector || '.dinofolio-related-carousel-viewport';
		var trackSelector = options.trackSelector || '.dinofolio-related-carousel-track';
		var slideSelector = options.slideSelector || '.dinofolio-related-card';
		var section = options.section || carousel.closest('.dinofolio-related-projects');
		var minColumns = typeof options.minColumns === 'number' ? options.minColumns : 2;
		var maxColumns = typeof options.maxColumns === 'number' ? options.maxColumns : 5;
		var defaultColumns = typeof options.defaultColumns === 'number' ? options.defaultColumns : 3;

		var viewport = carousel.querySelector(viewportSelector);
		var track = carousel.querySelector(trackSelector);
		var prevButton = carousel.querySelector('.dinofolio-carousel-prev');
		var nextButton = carousel.querySelector('.dinofolio-carousel-next');

		if (!viewport || !track || !prevButton || !nextButton) {
			return;
		}

		var getVisibleColumns = function () {
			var columns = defaultColumns;

			if (carousel.dataset.columns) {
				columns = parseInt(carousel.dataset.columns, 10);
			} else if (section) {
				var sectionColumns = parseInt(
					window.getComputedStyle(section).getPropertyValue('--dinofolio-related-columns'),
					10
				);
				if (!isNaN(sectionColumns)) {
					columns = sectionColumns;
				}
			}

			columns = Math.max(minColumns, Math.min(maxColumns, columns || defaultColumns));

			if (typeof options.getVisibleColumns === 'function') {
				return options.getVisibleColumns(columns);
			}

			if (window.matchMedia('(max-width: 640px)').matches) {
				return 1;
			}

			if (window.matchMedia('(max-width: 900px)').matches) {
				return Math.min(columns, 2);
			}

			return columns;
		};

		var getGap = function () {
			var styles = window.getComputedStyle(track);
			return parseFloat(styles.columnGap || styles.gap) || 16;
		};

		var updateCardWidths = function () {
			var columns = getVisibleColumns();
			var gap = getGap();
			var viewportWidth = viewport.clientWidth;
			var cardWidth = (viewportWidth - gap * (columns - 1)) / columns;

			if (cardWidth < 1) {
				return;
			}

			track.querySelectorAll(slideSelector).forEach(function (card) {
				card.style.flexBasis = cardWidth + 'px';
				card.style.width = cardWidth + 'px';
				card.style.maxWidth = cardWidth + 'px';
			});
		};

		var getScrollStep = function () {
			var card = track.querySelector(slideSelector);

			if (!card) {
				return viewport.clientWidth * 0.85;
			}

			return card.offsetWidth + getGap();
		};

		var getMaxScroll = function () {
			return Math.max(0, viewport.scrollWidth - viewport.clientWidth);
		};

		var canLoop = function () {
			return getMaxScroll() > 1;
		};

		var isAtStart = function () {
			return viewport.scrollLeft <= 1;
		};

		var isAtEnd = function () {
			return viewport.scrollLeft >= getMaxScroll() - 1;
		};

		var updateNavState = function () {
			if (canLoop()) {
				prevButton.disabled = false;
				nextButton.disabled = false;
				return;
			}

			prevButton.disabled = isAtStart();
			nextButton.disabled = isAtEnd();
		};

		var scrollByStep = function (direction) {
			if (!canLoop()) {
				viewport.scrollBy({
					left: direction * getScrollStep(),
					behavior: 'smooth',
				});
				return;
			}

			if (direction > 0 && isAtEnd()) {
				viewport.scrollTo({
					left: 0,
					behavior: 'smooth',
				});
				return;
			}

			if (direction < 0 && isAtStart()) {
				viewport.scrollTo({
					left: getMaxScroll(),
					behavior: 'auto',
				});
				return;
			}

			viewport.scrollBy({
				left: direction * getScrollStep(),
				behavior: 'smooth',
			});
		};

		prevButton.addEventListener('click', function () {
			scrollByStep(-1);
		});

		nextButton.addEventListener('click', function () {
			scrollByStep(1);
		});

		viewport.addEventListener('scroll', updateNavState, { passive: true });
		window.addEventListener('resize', function () {
			updateCardWidths();
			updateNavState();
		});

		updateCardWidths();
		updateNavState();
	};

	document.querySelectorAll('[data-dinofolio-gallery-carousel]').forEach(function (carousel) {
		initHorizontalCarousel(carousel, {
			viewportSelector: '.dinofolio-gallery-carousel-viewport',
			trackSelector: '.dinofolio-gallery-carousel-track',
			slideSelector: '.dinofolio-gallery-slide',
			minColumns: 1,
			maxColumns: 1,
			defaultColumns: 1,
			getVisibleColumns: function () {
				return 1;
			},
		});
	});

	var carousels = document.querySelectorAll('[data-dinofolio-carousel]');

	carousels.forEach(function (carousel) {
		initHorizontalCarousel(carousel);
	});
})();
