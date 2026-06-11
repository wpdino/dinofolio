jQuery(function($) {
	var hasUnsavedMetaChanges = false;
	var i18n = (window.wpdinoPortfolioMeta && window.wpdinoPortfolioMeta.i18n) ? window.wpdinoPortfolioMeta.i18n : {};

	var markDirty = function() {
		hasUnsavedMetaChanges = true;
	};

	var clearDirty = function() {
		hasUnsavedMetaChanges = false;
	};

	var $dateField = $('#wpdino_date_of_work');

	if ($dateField.length) {
		$dateField.datepicker({
			dateFormat: 'dd.mm.yy',
			changeMonth: true,
			changeYear: true,
			yearRange: '1900:2100',
		});
	}

	var $toggleGroups = $('.wpdino-toggle-group');
	var syncToggleState = function($group) {
		$group.find('label').removeClass('is-checked');
		$group.find('input[type="radio"]:checked').each(function() {
			$(this).closest('label').addClass('is-checked');
		});
	};

	if ($toggleGroups.length) {
		$toggleGroups.each(function() {
			syncToggleState($(this));
		});

		$(document).on('change', '.wpdino-toggle-group input[type="radio"]', function() {
			syncToggleState($(this).closest('.wpdino-toggle-group'));
		});
	}

	var syncFormatGroupState = function($scope) {
		$scope.find('label').removeClass('is-checked');
		$scope.find('input[type="radio"]:checked').each(function() {
			$(this).closest('label').addClass('is-checked');
		});
	};

	$('.wpdino-gallery-metabox .wpdino-format-group').each(function() {
		syncFormatGroupState($(this));
	});

	$(document).on('change', '.wpdino-gallery-metabox .wpdino-format-group input[type="radio"]', function() {
		syncFormatGroupState($(this).closest('.wpdino-format-group'));
		markDirty();
	});

	var syncFeaturedImageSizeVisibility = function() {
		var selectedValue = $('input[name="wpdino_featured_image_display"]:checked').val();
		var shouldShow = selectedValue !== 'off';
		$('.wpdino-featured-image-size-row').toggle(shouldShow);
	};

	syncFeaturedImageSizeVisibility();
	$(document).on('change', 'input[name="wpdino_featured_image_display"]', syncFeaturedImageSizeVisibility);

	var $galleryMetabox = $('#wpdino_portfolio_gallery');

	var getSelectedPostFormat = function() {
		if (window.wp && wp.data && wp.data.select) {
			try {
				var editor = wp.data.select('core/editor');
				if (editor && editor.getEditedPostAttribute) {
					return editor.getEditedPostAttribute('format') || '';
				}
			} catch (error) {
				// Fall back to the classic Format metabox controls.
			}
		}

		var $checked = $('input[name="post_format"]:checked');
		if ($checked.length) {
			return $checked.val() || '';
		}

		var $select = $('#post-formats-select');
		if ($select.length) {
			return $select.val() || '';
		}

		return '';
	};

	var limitPortfolioPostFormats = function() {
		$('#post-formats input[name="post_format"]').each(function() {
			var value = $(this).val() || '';
			if (value !== '' && value !== 'gallery') {
				$(this).closest('label').remove();
			}
		});

		$('#post-formats-select option').each(function() {
			var value = $(this).val() || '';
			if (value !== '' && value !== 'gallery') {
				$(this).remove();
			}
		});
	};

	var syncGalleryMetaboxVisibility = function() {
		var isGallery = getSelectedPostFormat() === 'gallery';

		if ($galleryMetabox.length) {
			$galleryMetabox.toggle(isGallery);
		}
	};

	limitPortfolioPostFormats();
	syncGalleryMetaboxVisibility();

	$(document).on('change', 'input[name="post_format"], #post-formats-select', syncGalleryMetaboxVisibility);

	if (window.wp && wp.data && wp.data.subscribe) {
		var lastPostFormat = getSelectedPostFormat();

		wp.data.subscribe(function() {
			var currentPostFormat = getSelectedPostFormat();
			if (currentPostFormat === lastPostFormat) {
				return;
			}

			lastPostFormat = currentPostFormat;
			syncGalleryMetaboxVisibility();
		});
	}

	var $galleryList = $('#wpdino-gallery-images');
	var galleryFrame = null;

	var buildGalleryItem = function(attachment) {
		var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
		var removeLabel = i18n.removeImage || 'Remove image';
		var dragTitle = i18n.dragToReorder || 'Drag to reorder';

		return $('<li class="wpdino-gallery-item"></li>')
			.attr('data-id', attachment.id)
			.append($('<span class="wpdino-gallery-drag" aria-hidden="true"></span>').attr('title', dragTitle))
			.append($('<img />').attr({ src: thumbUrl, alt: '' }))
			.append(
				$('<button type="button" class="wpdino-gallery-remove" aria-label="' + removeLabel + '">&times;</button>')
			)
			.append($('<input type="hidden" name="wpdino_gallery_images[]" />').val(attachment.id));
	};

	if ($galleryList.length) {
		$galleryList.sortable({
			items: '> .wpdino-gallery-item',
			handle: '.wpdino-gallery-drag, img',
			placeholder: 'wpdino-gallery-placeholder',
			forcePlaceholderSize: true,
			tolerance: 'pointer',
			update: function() {
				markDirty();
			},
		});
	}

	$(document).on('click', '#wpdino-gallery-add', function(event) {
		event.preventDefault();

		if (galleryFrame) {
			galleryFrame.open();
			return;
		}

		galleryFrame = wp.media({
			title: i18n.selectImages || 'Select Gallery Images',
			button: { text: i18n.insertImages || 'Add to Gallery' },
			multiple: true,
			library: { type: 'image' },
		});

		galleryFrame.on('select', function() {
			var selection = galleryFrame.state().get('selection');
			var existingIds = {};

			$galleryList.find('.wpdino-gallery-item').each(function() {
				existingIds[$(this).data('id')] = true;
			});

			selection.each(function(attachmentModel) {
				var attachment = attachmentModel.toJSON();
				if (existingIds[attachment.id]) {
					return;
				}
				$galleryList.append(buildGalleryItem(attachment));
				existingIds[attachment.id] = true;
			});

			markDirty();
		});

		galleryFrame.open();
	});

	$(document).on('click', '.wpdino-gallery-remove', function(event) {
		event.preventDefault();
		$(this).closest('.wpdino-gallery-item').remove();
		markDirty();
	});

	var syncImageSelectState = function($group) {
		$group.find('.wpdino-image-select-option').removeClass('selected');
		$group.find('input[type="radio"]:checked').each(function() {
			$(this).closest('.wpdino-image-select-option').addClass('selected');
		});
	};

	$('.wpdino-image-select-group').each(function() {
		syncImageSelectState($(this));
	});

	$(document).on('change', '.wpdino-image-select-group input[type="radio"]', function() {
		syncImageSelectState($(this).closest('.wpdino-image-select-group'));
	});

	var $relatedCountRange = $('#wpdino_related_projects_number');
	var $relatedCountValue = $('#wpdino_related_projects_number_value');

	if ($relatedCountRange.length && $relatedCountValue.length) {
		$relatedCountRange.on('input change', function() {
			$relatedCountValue.text($(this).val());
		});
	}

	// Track changes inside the portfolio meta boxes.
	$(document).on('input change', '#wpdino_portfolio_meta input, #wpdino_portfolio_meta select, #wpdino_portfolio_meta textarea, #wpdino_portfolio_gallery input, #wpdino_portfolio_gallery button', function() {
		markDirty();
	});

	// Mark dynamic row add/remove as unsaved changes too.
	$(document).on('click', '#wpdino_add_attribute, #wpdino-add-attribute, .wpdino-remove-attr', function() {
		markDirty();
	});

	// Clear dirty state when submitting/saving the post.
	$(document).on('submit', '#post', function() {
		clearDirty();
	});

	$(document).on('click', '#publish, #save-post, #post-preview, .editor-post-publish-button, .editor-post-save-draft', function() {
		clearDirty();
	});

	window.addEventListener('beforeunload', function(event) {
		if (!hasUnsavedMetaChanges) {
			return;
		}

		event.preventDefault();
		event.returnValue = '';
	});
});
