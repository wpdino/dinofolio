jQuery(function($) {
	var hasUnsavedMetaChanges = false;

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

	var syncFeaturedImageSizeVisibility = function() {
		var selectedValue = $('input[name="wpdino_featured_image_display"]:checked').val();
		var shouldShow = selectedValue !== 'off';
		$('.wpdino-featured-image-size-row').toggle(shouldShow);
	};

	syncFeaturedImageSizeVisibility();
	$(document).on('change', 'input[name="wpdino_featured_image_display"]', syncFeaturedImageSizeVisibility);

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

	// Track changes inside the portfolio meta box.
	$(document).on('input change', '#wpdino_portfolio_meta input, #wpdino_portfolio_meta select, #wpdino_portfolio_meta textarea', function() {
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
