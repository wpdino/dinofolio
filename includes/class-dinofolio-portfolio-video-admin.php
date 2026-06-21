<?php
/**
 * Admin AJAX handlers for portfolio video features.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Portfolio video admin integration.
 */
class Portfolio_Video_Admin {

	/**
	 * Bootstrap hooks.
	 *
	 * @return void
	 */
	public static function init() {
		$actions = array(
			'dinofolio_fetch_video_thumbnail'  => 'ajax_fetch_video_thumbnail',
			'dinofolio_attach_video_thumbnail' => 'ajax_attach_video_thumbnail',
		);

		foreach ( $actions as $action => $method ) {
			add_action( 'wp_ajax_' . $action, array( __CLASS__, $method ) );
		}
	}

	/**
	 * Verify portfolio edit permissions and nonce.
	 *
	 * @param string $nonce_action Nonce action.
	 * @return array{post_id:int,url:string}
	 */
	private static function verify_request( $nonce_action ) {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $nonce_action ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'dinofolio' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

		if ( $post_id < 1 || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to edit this item.', 'dinofolio' ) ) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		return array(
			'post_id' => $post_id,
			'url'     => $url,
		);
	}

	/**
	 * Fetch a YouTube/Vimeo thumbnail preview.
	 *
	 * @return void
	 */
	public static function ajax_fetch_video_thumbnail() {
		$request = self::verify_request( 'dinofolio_portfolio_video_admin' );

		$data = Portfolio_Video_Thumb::fetch_video_thumbnail( $request['url'], $request['post_id'] );

		if ( false === $data ) {
			wp_send_json_error( array( 'message' => __( 'Unable to fetch a thumbnail for this video URL.', 'dinofolio' ) ) );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Sideload a video thumbnail and set it as the featured image.
	 *
	 * @return void
	 */
	public static function ajax_attach_video_thumbnail() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to upload files.', 'dinofolio' ) ) );
		}

		$request = self::verify_request( 'dinofolio_portfolio_video_admin' );
		$id      = Portfolio_Video_Thumb::attach_remote_video_thumb( $request['url'], $request['post_id'] );

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Unable to save the video thumbnail.', 'dinofolio' ) ) );
		}

		set_post_thumbnail( $request['post_id'], $id );

		if ( (int) get_post_thumbnail_id( $request['post_id'] ) !== (int) $id ) {
			wp_send_json_error( array( 'message' => __( 'Thumbnail saved, but featured image could not be updated.', 'dinofolio' ) ) );
		}

		wp_send_json_success(
			array(
				'attachment_id'  => $id,
				'thumbnail_html' => _wp_post_thumbnail_html( $id, $request['post_id'] ),
				'thumbnail_url'  => wp_get_attachment_image_url( $id, 'thumbnail' ),
			)
		);
	}
}
