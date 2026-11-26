<?php
/**
 * Portfolio item video helpers (lightbox).
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Resolve and normalize per-item portfolio lightbox video settings.
 */
class Portfolio_Video {

	/**
	 * Supported video source types.
	 *
	 * @var string[]
	 */
	const TYPES = array( 'mp4', 'youtube', 'vimeo' );

	/**
	 * Get lightbox video settings for a portfolio item.
	 *
	 * @param int $post_id Post ID.
	 * @return array{lightbox:array}
	 */
	public static function get_item_settings( $post_id ) {
		$post_id = absint( $post_id );

		return array(
			'lightbox' => self::get_lightbox_settings( $post_id ),
		);
	}

	/**
	 * Get lightbox video settings for a portfolio item.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_lightbox_settings( $post_id ) {
		$enabled = 'on' === get_post_meta( $post_id, '_wpdino_video_lightbox', true );
		$type    = self::normalize_type( get_post_meta( $post_id, '_wpdino_video_lightbox_type', true ) );
		$url     = (string) get_post_meta( $post_id, '_wpdino_video_lightbox_url', true );
		$mp4_id  = absint( get_post_meta( $post_id, '_wpdino_video_lightbox_mp4_id', true ) );
		$source  = self::resolve_source_url( $type, $url, $mp4_id );

		return array(
			'enabled' => $enabled && ! empty( $source ),
			'type'    => $type,
			'url'     => $source,
			'mp4_id'  => $mp4_id,
			'raw_url' => $url,
		);
	}

	/**
	 * Normalize a stored video type.
	 *
	 * @param string $type Raw type.
	 * @return string
	 */
	public static function normalize_type( $type ) {
		$type = sanitize_key( (string) $type );

		return in_array( $type, self::TYPES, true ) ? $type : 'mp4';
	}

	/**
	 * Resolve the playable URL for a video source.
	 *
	 * @param string $type   Video type.
	 * @param string $url    External URL.
	 * @param int    $mp4_id Attachment ID for MP4 uploads.
	 * @return string
	 */
	public static function resolve_source_url( $type, $url, $mp4_id = 0 ) {
		$type = self::normalize_type( $type );

		if ( 'mp4' === $type ) {
			if ( $mp4_id > 0 ) {
				$attachment_url = wp_get_attachment_url( $mp4_id );
				if ( $attachment_url ) {
					return esc_url_raw( $attachment_url );
				}
			}

			return self::sanitize_video_url( 'mp4', $url );
		}

		return self::sanitize_video_url( $type, $url );
	}

	/**
	 * Sanitize a video URL for the given type.
	 *
	 * @param string $type Video type.
	 * @param string $url  Raw URL.
	 * @return string
	 */
	public static function sanitize_video_url( $type, $url ) {
		$type = self::normalize_type( $type );
		$url  = trim( (string) $url );

		if ( '' === $url ) {
			return '';
		}

		$url = esc_url_raw( $url );

		if ( '' === $url ) {
			return '';
		}

		if ( 'mp4' === $type ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			if ( is_string( $path ) && preg_match( '/\.mp4$/i', $path ) ) {
				return $url;
			}

			return '';
		}

		if ( 'youtube' === $type && self::get_youtube_id( $url ) ) {
			return $url;
		}

		if ( 'vimeo' === $type && self::get_vimeo_id( $url ) ) {
			return $url;
		}

		return '';
	}

	/**
	 * Extract a YouTube video ID from a URL.
	 *
	 * @param string $url Video URL.
	 * @return string
	 */
	public static function get_youtube_id( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return '';
		}

		$patterns = array(
			'#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#i',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $matches ) ) {
				return $matches[1];
			}
		}

		return '';
	}

	/**
	 * Extract a Vimeo video ID from a URL.
	 *
	 * @param string $url Video URL.
	 * @return string
	 */
	public static function get_vimeo_id( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url || ! preg_match( '#vimeo\.com/(?:video/)?(\d+)#i', $url, $matches ) ) {
			return '';
		}

		return $matches[1];
	}

	/**
	 * Sanitize an MP4 attachment ID.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return int
	 */
	public static function sanitize_mp4_attachment_id( $attachment_id ) {
		$attachment_id = absint( $attachment_id );

		if ( $attachment_id < 1 ) {
			return 0;
		}

		$mime = get_post_mime_type( $attachment_id );

		if ( ! $mime || 0 !== strpos( $mime, 'video/' ) ) {
			return 0;
		}

		return $attachment_id;
	}

	/**
	 * Store cached lightbox video duration (seconds) for listing badges.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function update_lightbox_duration_meta( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id < 1 ) {
			return;
		}

		$settings = self::get_lightbox_settings( $post_id );
		$seconds  = self::resolve_lightbox_duration_seconds( $settings );

		if ( $seconds > 0 ) {
			update_post_meta( $post_id, '_wpdino_video_lightbox_duration', $seconds );
			return;
		}

		delete_post_meta( $post_id, '_wpdino_video_lightbox_duration' );
	}

	/**
	 * Human-readable duration for lightbox video badges.
	 *
	 * @param int        $post_id  Post ID.
	 * @param array|null $settings Optional lightbox settings.
	 * @return string
	 */
	public static function get_lightbox_duration_label( $post_id, $settings = null ) {
		$post_id = absint( $post_id );
		$seconds = absint( get_post_meta( $post_id, '_wpdino_video_lightbox_duration', true ) );

		if ( $seconds < 1 ) {
			$settings = is_array( $settings ) ? $settings : self::get_lightbox_settings( $post_id );
			$seconds  = self::resolve_lightbox_duration_seconds( $settings );
		}

		if ( $seconds < 1 ) {
			return '';
		}

		return self::format_duration( $seconds );
	}

	/**
	 * Resolve video duration in seconds.
	 *
	 * @param array $settings Lightbox settings.
	 * @return int
	 */
	private static function resolve_lightbox_duration_seconds( $settings ) {
		if ( empty( $settings['enabled'] ) ) {
			return 0;
		}

		$type = isset( $settings['type'] ) ? self::normalize_type( $settings['type'] ) : 'mp4';

		if ( 'mp4' === $type ) {
			$mp4_id = isset( $settings['mp4_id'] ) ? absint( $settings['mp4_id'] ) : 0;
			if ( $mp4_id < 1 ) {
				return 0;
			}

			$meta = wp_get_attachment_metadata( $mp4_id );
			if ( ! is_array( $meta ) || empty( $meta['length'] ) ) {
				return 0;
			}

			return max( 0, (int) round( (float) $meta['length'] ) );
		}

		if ( 'vimeo' === $type ) {
			return self::fetch_vimeo_duration_seconds( isset( $settings['url'] ) ? $settings['url'] : '' );
		}

		return 0;
	}

	/**
	 * Fetch Vimeo duration via oEmbed.
	 *
	 * @param string $url Video URL.
	 * @return int
	 */
	private static function fetch_vimeo_duration_seconds( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return 0;
		}

		$endpoint = 'https://vimeo.com/api/oembed.json?url=' . rawurlencode( esc_url_raw( $url ) );
		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout' => 5,
			)
		);

		if ( is_wp_error( $response ) ) {
			return 0;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['duration'] ) ) {
			return 0;
		}

		return max( 0, (int) $body['duration'] );
	}

	/**
	 * Format seconds as m:ss or h:mm:ss.
	 *
	 * @param int $seconds Duration.
	 * @return string
	 */
	private static function format_duration( $seconds ) {
		$seconds = max( 0, (int) $seconds );
		$hours   = (int) floor( $seconds / 3600 );
		$minutes = (int) floor( ( $seconds % 3600 ) / 60 );
		$secs    = $seconds % 60;

		if ( $hours > 0 ) {
			return sprintf( '%d:%02d:%02d', $hours, $minutes, $secs );
		}

		return sprintf( '%d:%02d', $minutes, $secs );
	}
}
