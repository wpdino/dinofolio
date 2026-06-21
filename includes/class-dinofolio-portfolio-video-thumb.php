<?php
/**
 * Fetch and sideload YouTube/Vimeo thumbnails as portfolio featured images.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Video thumbnail helpers for portfolio items.
 */
class Portfolio_Video_Thumb {

	/**
	 * YouTube thumbnail size fallbacks.
	 *
	 * @var string[]
	 */
	const YOUTUBE_SIZES = array( 'maxresdefault', 'hqdefault', 'mqdefault', 'default', 'sddefault' );

	/**
	 * Fetch thumbnail data for a YouTube or Vimeo URL.
	 *
	 * @param string $url     Video URL.
	 * @param int    $post_id Portfolio post ID.
	 * @return array|false
	 */
	public static function fetch_video_thumbnail( $url, $post_id ) {
		$url     = trim( (string) $url );
		$post_id = absint( $post_id );

		if ( '' === $url || ! filter_var( $url, FILTER_VALIDATE_URL ) || $post_id < 1 ) {
			return false;
		}

		$youtube_id = Portfolio_Video::get_youtube_id( $url );

		if ( $youtube_id ) {
			$thumb_url = self::get_youtube_thumbnail( $youtube_id );
		} else {
			$thumb_url = self::get_oembed_thumbnail_url( $url );
		}

		if ( ! $thumb_url ) {
			return false;
		}

		return array(
			'thumb_url'           => $thumb_url,
			'is_already_featured' => self::thumb_is_featured( $thumb_url, $post_id ),
		);
	}

	/**
	 * Download a remote thumbnail and attach it to a portfolio post.
	 *
	 * @param string $url     Video URL used to resolve the thumbnail.
	 * @param int    $post_id Portfolio post ID.
	 * @return int|false Attachment ID.
	 */
	public static function attach_remote_video_thumb( $url, $post_id ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return false;
		}

		$fetch = self::fetch_video_thumbnail( $url, $post_id );

		if ( false === $fetch || empty( $fetch['thumb_url'] ) ) {
			return false;
		}

		$thumb_url = $fetch['thumb_url'];
		$existing  = self::find_attachment_by_original_url( $thumb_url, $post_id );

		if ( $existing > 0 ) {
			return $existing;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_sideload_image( $thumb_url, $post_id, null, 'id' );

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return false;
		}

		update_post_meta( $attachment_id, '_wpdino_original_thumb_url', esc_url_raw( $thumb_url ) );

		return (int) $attachment_id;
	}

	/**
	 * Get the best available YouTube thumbnail URL.
	 *
	 * @param string $video_id YouTube video ID.
	 * @return string
	 */
	public static function get_youtube_thumbnail( $video_id ) {
		$video_id = sanitize_text_field( (string) $video_id );

		if ( '' === $video_id ) {
			return '';
		}

		foreach ( self::YOUTUBE_SIZES as $size ) {
			$url = sprintf(
				'https://img.youtube.com/vi/%s/%s.jpg',
				rawurlencode( $video_id ),
				$size
			);

			$response = wp_safe_remote_head( $url );

			if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * Get a thumbnail URL via WordPress oEmbed.
	 *
	 * @param string $url Video URL.
	 * @return string
	 */
	public static function get_oembed_thumbnail_url( $url ) {
		if ( ! function_exists( '_wp_oembed_get_object' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-oembed.php';
		}

		$oembed   = _wp_oembed_get_object();
		$provider = $oembed->get_provider( $url );

		if ( ! $provider ) {
			return '';
		}

		$data = $oembed->fetch( $provider, $url, array( 'width' => 1280 ) );

		if ( ! $data || empty( $data->thumbnail_url ) ) {
			return '';
		}

		$thumb_url = (string) $data->thumbnail_url;

		if ( false !== strpos( $provider, 'youtube' ) ) {
			$stripped = str_replace( basename( $thumb_url ), '', $thumb_url );
			$maxres   = $stripped . 'maxresdefault.jpg';
			$response = wp_safe_remote_head( $maxres );

			if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
				return $maxres;
			}
		}

		return esc_url_raw( $thumb_url );
	}

	/**
	 * Whether the given thumbnail URL is already the post featured image.
	 *
	 * @param string $thumb_url Thumbnail URL.
	 * @param int    $post_id   Post ID.
	 * @return bool
	 */
	public static function thumb_is_featured( $thumb_url, $post_id ) {
		$attachment_id = self::find_attachment_by_original_url( $thumb_url, $post_id );

		if ( $attachment_id < 1 ) {
			return false;
		}

		return (int) get_post_thumbnail_id( $post_id ) === $attachment_id;
	}

	/**
	 * Find an existing attachment created from a remote thumbnail URL.
	 *
	 * @param string $thumb_url Thumbnail URL.
	 * @param int    $post_id   Post ID.
	 * @return int
	 */
	public static function find_attachment_by_original_url( $thumb_url, $post_id ) {
		$thumb_url = esc_url_raw( (string) $thumb_url );
		$post_id   = absint( $post_id );

		if ( '' === $thumb_url || $post_id < 1 ) {
			return 0;
		}

		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'posts_per_page' => 1,
				'post_parent'    => $post_id,
				'meta_key'       => '_wpdino_original_thumb_url',
				'meta_value'     => $thumb_url,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $attachments[0] ) ) {
			return (int) $attachments[0];
		}

		return 0;
	}
}
