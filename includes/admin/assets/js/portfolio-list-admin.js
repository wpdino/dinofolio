jQuery(function($) {
	var config = window.wpdinoPortfolioListAdmin || {};
	var i18n = config.i18n || {};
	var ajaxUrl = config.ajaxUrl || window.ajaxurl;
	var nonce = config.nonce || '';

	$(document).on('click', '.wpdino-add-featured-image, .wpdino-change-featured-image', function(event) {
		event.preventDefault();

		var $trigger = $(this);
		var postId = $trigger.data('post-id');
		var $cell = $trigger.closest('td');

		var mediaFrame = wp.media({
			title: i18n.selectFeaturedImage || 'Select Featured Image',
			button: { text: i18n.setFeaturedImage || 'Set Featured Image' },
			multiple: false,
		});

		mediaFrame.on('select', function() {
			var attachment = mediaFrame.state().get('selection').first().toJSON();

			$.post(ajaxUrl, {
				action: 'wpdino_portfolio_save_featured_image',
				post_id: postId,
				image_id: attachment.id,
				action_type: 'set',
				nonce: nonce,
			}, function(response) {
				if (response.success && response.data && response.data.cell_html) {
					$cell.html(response.data.cell_html);
					return;
				}

				window.alert(i18n.errorUpdating || 'Error updating featured image');
			});
		});

		mediaFrame.open();
	});

	$(document).on('click', '.wpdino-remove-featured-image', function(event) {
		event.preventDefault();

		var $trigger = $(this);
		var postId = $trigger.data('post-id');
		var $cell = $trigger.closest('td');

		if (!window.confirm(i18n.confirmRemove || 'Are you sure you want to remove the featured image?')) {
			return;
		}

		$.post(ajaxUrl, {
			action: 'wpdino_portfolio_save_featured_image',
			post_id: postId,
			action_type: 'remove',
			nonce: nonce,
		}, function(response) {
			if (response.success && response.data && response.data.cell_html) {
				$cell.html(response.data.cell_html);
				return;
			}

			window.alert(i18n.errorRemoving || 'Error removing featured image');
		});
	});
});
