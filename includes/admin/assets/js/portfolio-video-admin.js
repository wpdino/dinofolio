jQuery(function($) {
	var config = window.wpdinoPortfolioVideo || {};
	var i18n = config.i18n || {};
	var thumbTimer = null;

	function getPostId() {
		if (window.wp && wp.data && wp.data.select) {
			try {
				var editorId = wp.data.select('core/editor').getCurrentPostId();
				if (editorId) {
					return editorId;
				}
			} catch (error) {
				// Fall back to the classic editor hidden input.
			}
		}

		return $('#post_ID').val() || 0;
	}

	function applyFeaturedImage(attachmentId, thumbnailHtml) {
		if (window.wp && wp.data && wp.data.dispatch) {
			try {
				wp.data.dispatch('core/editor').editPost({
					featured_media: parseInt(attachmentId, 10) || 0
				});
			} catch (error) {
				// Continue to classic editor fallbacks.
			}
		}

		if (window.WPSetThumbnailID && window.WPSetThumbnailHTML) {
			window.WPSetThumbnailID(attachmentId);
			window.WPSetThumbnailHTML(thumbnailHtml);
		}

		var $postImageDiv = $('#postimagediv');

		if ($postImageDiv.length && thumbnailHtml) {
			$postImageDiv.find('.inside').html(thumbnailHtml);
		}
	}

	function ajax(action, data) {
		return $.post(
			config.ajaxUrl || window.ajaxurl,
			$.extend(
				{
					action: action,
					nonce: config.nonce,
					post_id: getPostId()
				},
				data || {}
			)
		);
	}

	function setThumbPreview(data) {
		var $preview = $('[data-wpdino-video-thumb-preview="lightbox"]');

		if (!$preview.length) {
			return;
		}

		if (!data || !data.thumb_url) {
			$preview.prop('hidden', true);
			$preview.find('img').attr('src', '');
			return;
		}

		$preview.prop('hidden', false);
		$preview.find('img').attr('src', data.thumb_url);

		var $button = $preview.find('.wpdino-set-featured-from-video');
		var $status = $preview.find('.wpdino-video-thumb-status');

		if (data.is_already_featured) {
			$button.prop('disabled', true).text(i18n.featuredImageSet || 'This is the Featured Image');
			$status.text('');
		} else {
			$button.prop('disabled', false).text(i18n.useFeaturedImage || 'Use as Featured Image');
			$status.text('');
		}
	}

	function fetchThumbPreview() {
		var url = $.trim($('#video_lightbox_url').val() || '');

		if (!url) {
			setThumbPreview(null);
			return;
		}

		ajax('dinofolio_fetch_video_thumbnail', { url: url }).done(function(response) {
			if (response.success) {
				setThumbPreview(response.data);
			} else {
				setThumbPreview(null);
			}
		});
	}

	function scheduleThumbPreview() {
		window.clearTimeout(thumbTimer);
		thumbTimer = window.setTimeout(fetchThumbPreview, 700);
	}

	$(document).on('input', '#video_lightbox_url', scheduleThumbPreview);

	$(document).on('click', '.wpdino-set-featured-from-video', function(event) {
		event.preventDefault();

		var url = $.trim($('#video_lightbox_url').val() || '');
		var $button = $(this);

		if (!url) {
			return;
		}

		$button.prop('disabled', true);

		ajax('dinofolio_attach_video_thumbnail', { url: url }).done(function(response) {
			if (!response.success) {
				$button.prop('disabled', false);
				window.alert(response.data && response.data.message ? response.data.message : (i18n.thumbError || 'Unable to set featured image.'));
				return;
			}

			applyFeaturedImage(response.data.attachment_id, response.data.thumbnail_html);
			fetchThumbPreview();
		}).fail(function() {
			$button.prop('disabled', false);
		});
	});

	if ($.trim($('#video_lightbox_url').val())) {
		fetchThumbPreview();
	}
});
