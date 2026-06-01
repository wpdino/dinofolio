<?php
/**
 * REST API Endpoints for WPDINO Portfolio
 *
 * @package WPDINO_Portfolio
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for handling REST API endpoints
 */
class WPDINO_Portfolio_REST_API {

	/**
	 * @var WPDINO_Portfolio_REST_API The reference to *Singleton* instance of this class
	 */
	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return WPDINO_Portfolio_REST_API The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		
		// Register image sizes endpoint
		register_rest_route(
			'wpdino-blocks/v1',
			'/image-sizes',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_image_sizes' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			)
		);

		// Register portfolio posts endpoint
		register_rest_route(
			'wpdino-blocks/v1',
			'/posts',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_portfolio_posts' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
				'args'                => $this->get_posts_params(),
			)
		);

		// Register categories endpoint
		register_rest_route(
			'wpdino-blocks/v1',
			'/categories',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_categories' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			)
		);
	}

	/**
	 * Permission check for API endpoints.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return bool True if the request has read access, false otherwise.
	 */
	public function get_permissions_check( $request ) {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Get available image sizes.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_image_sizes( $request ) {
		
		$image_sizes = array();
		$sizes = get_intermediate_image_sizes();
		
		foreach ( $sizes as $size ) {
			$image_sizes[] = array(
				'label' => ucfirst( str_replace( '_', ' ', $size ) ),
				'value' => $size,
			);
		}
		
		// Add full size
		$image_sizes[] = array(
			'label' => esc_html__( 'Full Size', 'dinofolio' ),
			'value' => 'full',
		);

		return rest_ensure_response( $image_sizes );
	}

	/**
	 * Get portfolio posts.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_portfolio_posts( $request ) {
		
		$args = array(
			'post_type'      => 'portfolio',
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ) ?: 10,
			'orderby'        => $request->get_param( 'orderby' ) ?: 'date',
			'order'          => $request->get_param( 'order' ) ?: 'DESC',
		);

		if ( $request->get_param( 'categories' ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'portfolio_category',
					'field'    => 'term_id',
					'terms'    => $request->get_param( 'categories' ),
				),
			);
		}

		$posts = get_posts( $args );
		$data = array();

		foreach ( $posts as $post ) {
			$data[] = array(
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'excerpt'      => $post->post_excerpt ?: wp_trim_words( $post->post_content, 20 ),
				'permalink'    => get_permalink( $post->ID ),
				'featured_image' => get_the_post_thumbnail_url( $post->ID, 'large' ),
				'date'         => $post->post_date,
				'categories'   => wp_get_post_terms( $post->ID, 'portfolio_category', array( 'fields' => 'names' ) ),
			);
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get portfolio categories.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_categories( $request ) {
		
		$terms = get_terms( array(
			'taxonomy'   => 'portfolio_category',
			'hide_empty' => false,
		) );

		$data = array();
		
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$data[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get posts endpoint parameters.
	 */
	public function get_posts_params() {
		return array(
			'per_page' => array(
				'description' => esc_html__( 'Number of posts to retrieve.', 'dinofolio' ),
				'type'        => 'integer',
				'default'     => 12,
				'minimum'     => 1,
				'maximum'     => 100,
			),
			'order_by' => array(
				'description' => esc_html__( 'Sort posts by field.', 'dinofolio' ),
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => array( 'date', 'title', 'menu_order', 'rand' ),
			),
			'order' => array(
				'description' => esc_html__( 'Order sort attribute ascending or descending.', 'dinofolio' ),
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
			),
			'categories' => array(
				'description' => esc_html__( 'Limit result set to posts assigned to specific categories.', 'dinofolio' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
			),
		);
	}
} 