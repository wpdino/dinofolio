<?php
/**
 * WPDINO Portfolio Display Class
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPDINO_Portfolio_Display
 * 
 * Handles all frontend display functionality for portfolio posts
 */
class WPDINO_Portfolio_Display {

	/**
	 * Portfolio post type
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * Portfolio taxonomies
	 *
	 * @var array
	 */
	private $taxonomies;

	/**
	 * Settings instance
	 *
	 * @var WPDINO_Portfolio_Settings
	 */
	private $settings;

	/**
	 * Instance of this class
	 *
	 * @var WPDINO_Portfolio_Display
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance
	 *
	 * @return WPDINO_Portfolio_Display
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->post_type    = 'wpdino_portfolio';
		$this->taxonomies   = array( 'wpdino_portfolio_category', 'wpdino_portfolio_tag' );
		$this->settings     = WPDINO_Portfolio_Settings::instance();
		
		// Register shortcode
		add_shortcode( 'dinofolio', array( $this, 'shortcode_handler' ) );

		add_action( 'init', array( $this, 'register_listing_assets' ) );

		// Enqueue frontend assets only when needed
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Style handle for portfolio listing output.
	 *
	 * @return string
	 */
	public static function get_listing_style_handle() {
		return 'dinofolio-portfolio-listing';
	}

	/**
	 * Register portfolio listing styles (shared by all builders).
	 *
	 * @return void
	 */
	public function register_listing_assets() {
		wp_register_style(
			self::get_listing_style_handle(),
			DINOFOLIO_URL . 'assets/css/portfolio-listing.css',
			array(),
			DINOFOLIO_VERSION
		);

		wp_register_style(
			'dinofolio-portfolio-listing-editor',
			DINOFOLIO_URL . 'assets/css/portfolio-listing-editor.css',
			array( self::get_listing_style_handle() ),
			DINOFOLIO_VERSION
		);
	}

	/**
	 * Whether the listing is rendered inside the block editor preview (ServerSideRender).
	 *
	 * @return bool
	 */
	public static function is_block_editor_preview() {
		if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['context'] ) && 'edit' === sanitize_key( wp_unslash( $_GET['context'] ) );
	}

	/**
	 * Enqueue portfolio listing styles when the listing is rendered.
	 *
	 * @return void
	 */
	public function enqueue_listing_assets() {
		if ( ! wp_style_is( self::get_listing_style_handle(), 'registered' ) ) {
			$this->register_listing_assets();
		}

		wp_enqueue_style( self::get_listing_style_handle() );

		if ( self::is_block_editor_preview() ) {
			wp_enqueue_style( 'dinofolio-portfolio-listing-editor' );
		}
	}

	/**
	 * Static wrapper for get_portfolio_listing
	 *
	 * @param array $attributes The block attributes or shortcode attributes
	 * @param array $extra_args Additional query arguments
	 * @return string The portfolio listing HTML
	 */
	public static function get_portfolio_listing( $attributes = array(), $extra_args = array() ) {
		$instance = self::get_instance();
		return $instance->render_portfolio_listing( $attributes, $extra_args );
	}

	/**
	 * Get the portfolio listing with full frontend display
	 *
	 * @param array $attributes The block attributes or shortcode attributes
	 * @param array $extra_args Additional query arguments
	 * @return string The portfolio listing HTML
	 */
	public function render_portfolio_listing( $attributes = array(), $extra_args = array() ) {
		$this->enqueue_listing_assets();

		// Merge attributes with defaults from settings and block defaults
		$attributes = $this->merge_attributes_with_defaults( $attributes );
		
		// Build the query arguments
		$query_args = $this->build_query( $attributes, $extra_args );
		
		// Execute the query
		$portfolio_query = new WP_Query( $query_args );
		
		// Generate and return the HTML output
		return $this->generate_portfolio_html( $portfolio_query, $attributes );
	}

	/**
	 * Build the query arguments
	 *
	 * @param array $attributes The merged attributes
	 * @param array $extra_args Additional query arguments
	 * @return array WP_Query arguments
	 */
	public function build_query( $attributes, $extra_args = array() ) {
		$order_by = isset( $attributes['orderBy'] ) ? sanitize_key( $attributes['orderBy'] ) : 'date';
		$order    = isset( $attributes['order'] ) ? strtoupper( sanitize_key( $attributes['order'] ) ) : 'DESC';

		if ( 'menu_order' === $order_by ) {
			$orderby = 'menu_order date';
			$order   = 'ASC';
		} else {
			$orderby = $order_by;
		}

		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $attributes['postsToShow'],
			'orderby'        => $orderby,
			'order'          => $order,
			'meta_query'     => array(),
			'tax_query'      => array(),
		);

		$category_ids = class_exists( '\DinoFolio\Util' )
			? \DinoFolio\Util::sanitize_taxonomy_term_ids( isset( $attributes['categories'] ) ? $attributes['categories'] : array() )
			: array_filter( array_map( 'intval', (array) ( $attributes['categories'] ?? array() ) ) );

		if ( ! empty( $category_ids ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => $this->taxonomies[0],
				'field'    => 'term_id',
				'terms'    => $category_ids,
				'operator' => 'IN',
			);
		}

		$tag_ids = class_exists( '\DinoFolio\Util' )
			? \DinoFolio\Util::sanitize_taxonomy_term_ids( isset( $attributes['tags'] ) ? $attributes['tags'] : array() )
			: array_filter( array_map( 'intval', (array) ( $attributes['tags'] ?? array() ) ) );

		if ( ! empty( $tag_ids ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => $this->taxonomies[1],
				'field'    => 'term_id',
				'terms'    => $tag_ids,
				'operator' => 'IN',
			);
		}

		if ( ! empty( $args['tax_query'] ) && count( $args['tax_query'] ) > 1 ) {
			$args['tax_query']['relation'] = 'AND';
		}

		// Handle pagination - use standard WordPress pagination
		if ( isset( $attributes['paged'] ) ) {
			$args['paged'] = intval( $attributes['paged'] );
		} else {
			// Get current page from URL parameter
			$current_page = 1;
			
			// Check for 'paged' query var (for archive pages)
			if ( get_query_var( 'paged' ) ) {
				$current_page = max( 1, get_query_var( 'paged' ) );
			}
			// Check for 'page' query var (for shortcodes on pages)
			elseif ( get_query_var( 'page' ) ) {
				$current_page = max( 1, get_query_var( 'page' ) );
			}
			// Check for custom pagination parameter in URL
			elseif ( isset( $_GET['pg'] ) && is_numeric( $_GET['pg'] ) ) {
				$current_page = max( 1, intval( $_GET['pg'] ) );
			}
			
			$args['paged'] = $current_page;
		}

		// Handle search
		if ( ! empty( $attributes['search'] ) ) {
			$args['s'] = sanitize_text_field( $attributes['search'] );
		}

		// Handle author filter
		if ( ! empty( $attributes['author'] ) ) {
			$args['author'] = intval( $attributes['author'] );
		}

		// Handle date range
		if ( ! empty( $attributes['date_from'] ) || ! empty( $attributes['date_to'] ) ) {
			$date_query = array();
			
			if ( ! empty( $attributes['date_from'] ) ) {
				$date_query['after'] = sanitize_text_field( $attributes['date_from'] );
			}
			
			if ( ! empty( $attributes['date_to'] ) ) {
				$date_query['before'] = sanitize_text_field( $attributes['date_to'] );
			}
			
			if ( ! empty( $date_query ) ) {
				$args['date_query'] = array( $date_query );
			}
		}

		// Handle custom meta queries
		if ( ! empty( $attributes['meta_key'] ) && ! empty( $attributes['meta_value'] ) ) {
			$args['meta_query'][] = array(
				'key'     => sanitize_text_field( $attributes['meta_key'] ),
				'value'   => sanitize_text_field( $attributes['meta_value'] ),
				'compare' => isset( $attributes['meta_compare'] ) ? $attributes['meta_compare'] : '=',
			);
		}

		// Only featured items
		if ( ! empty( $attributes['featured_only'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS',
			);
		}

		// Exclude specific posts
		if ( ! empty( $attributes['exclude'] ) ) {
			$args['post__not_in'] = array_map( 'intval', (array) $attributes['exclude'] );
		}

		// Include specific posts
		if ( ! empty( $attributes['include'] ) ) {
			$args['post__in'] = array_map( 'intval', (array) $attributes['include'] );
		}

		// Clean up empty arrays
		if ( empty( $args['meta_query'] ) ) {
			unset( $args['meta_query'] );
		}
		if ( empty( $args['tax_query'] ) ) {
			unset( $args['tax_query'] );
		}

		// Merge with extra arguments
		$args = wp_parse_args( $extra_args, $args );

		// Apply filter for custom modifications
		return apply_filters( 'wpdino_portfolio_query_args', $args, $attributes );
	}

	/**
	 * Merge attributes with defaults from settings and block defaults
	 *
	 * @param array $attributes Input attributes
	 * @return array Merged attributes
	 */
	private function merge_attributes_with_defaults( $attributes ) {
		
		// Normalize alternative block attribute names coming from the JS block
		// Map JS block props → internal props used by display renderer
		$normalized = $attributes;
		// Map Gutenberg block style (is-style-*) from className to layout/style
		if ( ! empty( $normalized['className'] ) && is_string( $normalized['className'] ) ) {
			if ( strpos( $normalized['className'], 'is-style-grid' ) !== false ) {
				$normalized['layout'] = 'grid';
				$normalized['style']  = 'grid';
			}
			if ( strpos( $normalized['className'], 'is-style-overlay' ) !== false ) {
				$normalized['layout'] = 'grid';
				$normalized['style']  = 'overlay';
			}
			if ( strpos( $normalized['className'], 'is-style-masonry' ) !== false ) {
				$normalized['layout'] = 'masonry';
				$normalized['style']  = 'masonry';
			}
			if ( strpos( $normalized['className'], 'is-style-carousel' ) !== false ) {
				$normalized['layout'] = 'carousel';
				$normalized['style']  = 'carousel';
			}
		}
		if ( isset( $normalized['columnsAmount'] ) && ! isset( $normalized['columns'] ) ) {
			$normalized['columns'] = intval( $normalized['columnsAmount'] );
		}
		if ( isset( $normalized['amount'] ) && ! isset( $normalized['postsToShow'] ) ) {
			$normalized['postsToShow'] = intval( $normalized['amount'] );
		}
		if ( isset( $normalized['thumbnailSize'] ) && ! isset( $normalized['imageSize'] ) ) {
			$normalized['imageSize'] = sanitize_text_field( $normalized['thumbnailSize'] );
		}
		if ( isset( $normalized['showCategoryFilter'] ) && ! isset( $normalized['showFilter'] ) ) {
			$normalized['showFilter'] = (bool) $normalized['showCategoryFilter'];
		}
		// Ensure order/orderBy are lowercase strings
		if ( isset( $normalized['order'] ) ) {
			$normalized['order'] = strtolower( (string) $normalized['order'] );
		}
		if ( isset( $normalized['orderBy'] ) ) {
			$normalized['orderBy'] = sanitize_text_field( $normalized['orderBy'] );
		}
		if ( ! empty( $normalized['layout'] ) && 'masonry' === $normalized['layout'] ) {
			$normalized['style'] = 'masonry';
		}

		if ( isset( $normalized['categories'] ) && class_exists( '\DinoFolio\Util' ) ) {
			$normalized['categories'] = \DinoFolio\Util::sanitize_taxonomy_term_ids( $normalized['categories'] );
		}

		if ( isset( $normalized['tags'] ) && class_exists( '\DinoFolio\Util' ) ) {
			$normalized['tags'] = \DinoFolio\Util::sanitize_taxonomy_term_ids( $normalized['tags'] );
		}

		$enabled_features = $this->settings->get_setting( 'enabled_features', array( 'pagination' ) );
		if ( ! is_array( $enabled_features ) ) {
			$enabled_features = array( 'pagination' );
		}

		// Block/shortcode defaults
		$block_defaults = array(
			'align'          => 'center',
			'layout'         => $this->settings->get_setting( 'default_layout', 'grid' ),
			'columns'        => $this->settings->get_setting( 'columns', 3 ),
			'postsToShow'    => $this->settings->get_setting( 'items_per_page', 12 ),
			'showExcerpt'    => $this->settings->get_setting( 'show_excerpt', true ),
			'showReadMore'   => true,
			'readMoreLabel'  => esc_html__( 'View Project', 'dinofolio' ),
			'imageSize'      => $this->settings->get_setting( 'image_size', 'large' ),
			'orderBy'        => 'date',
			'order'          => 'desc',
			'categories'     => array(),
			'tags'           => array(),
			'showFilter'     => false,
			'filterDynamic'  => false,
			'showViewAll'    => false,
			'viewAllText'    => esc_html__( 'View All', 'dinofolio' ),
			'viewAllLink'    => '',
			'className'      => '',
			'showPagination' => in_array( 'pagination', $enabled_features, true ),
			'showTitle'      => true,
			'showMeta'       => true,
			'showCategories' => true,
			'lightbox'       => $this->settings->get_setting( 'enable_lightbox', true ),
			'hoverEffect'    => $this->settings->get_setting( 'hover_effect', 'zoom' ),
			'style'          => $this->settings->get_setting( 'portfolio_style', 'classic' ),
			'enableParallax' => true,
		);

		// Merge and sanitize
		$merged = wp_parse_args( $normalized, $block_defaults );

		// Type casting and validation
		$merged['columns']      = max( 1, min( 6, intval( $merged['columns'] ) ) );
		$merged['postsToShow']  = max( 1, min( 100, intval( $merged['postsToShow'] ) ) );
		$merged['showExcerpt']  = (bool) $merged['showExcerpt'];
		$merged['showReadMore'] = (bool) $merged['showReadMore'];
		$merged['showTitle']    = (bool) $merged['showTitle'];
		$merged['showCategories'] = (bool) $merged['showCategories'];
		$merged['showFilter']   = (bool) $merged['showFilter'];
		$merged['showViewAll']  = (bool) $merged['showViewAll'];
		$merged['showPagination'] = (bool) $merged['showPagination'];
		$merged['lightbox']     = (bool) $merged['lightbox'];

		// Validate layout
		$valid_layouts = array( 'grid', 'masonry', 'list' );
		if ( ! in_array( $merged['layout'], $valid_layouts ) ) {
			$merged['layout'] = 'grid';
		}

		// Validate order
		$merged['order'] = in_array( strtoupper( $merged['order'] ), array( 'ASC', 'DESC' ) ) ? $merged['order'] : 'desc';

		return apply_filters( 'wpdino_portfolio_merged_attributes', $merged, $attributes );
	}

	/**
	 * Generate the portfolio HTML output
	 *
	 * @param WP_Query $query The portfolio query
	 * @param array $attributes The merged attributes
	 * @return string HTML output
	 */
	private function generate_portfolio_html( $query, $attributes ) {
		
		if ( ! $query->have_posts() ) {
			return $this->get_no_posts_message( $attributes );
		}

		$output = '';
		
		// Container classes - match existing SCSS structure
		$container_classes = array(
			'wpdino-blocks_portfolio-block',
			'layout-' . $attributes['layout'],
			'columns-' . $attributes['columns'],
			'style-' . $attributes['style'],
			'hover-' . $attributes['hoverEffect'],
		);

		// Enable parallax class when requested (for any style)
		if ( ! empty( $attributes['enableParallax'] ) ) {
			$container_classes[] = 'parallax-enabled';
		}

		if ( ! empty( $attributes['className'] ) ) {
			$container_classes[] = $attributes['className'];
		}

		if ( $attributes['lightbox'] ) {
			$container_classes[] = 'lightbox-enabled';
		}

		if ( self::is_block_editor_preview() ) {
			$container_classes[] = 'dinofolio-listing--editor-preview';
		}

		// Start container
		$output .= '<div class="' . esc_attr( implode( ' ', $container_classes ) ) . '">';

		// Add filter if enabled
		if ( $attributes['showFilter'] ) {
			$output .= $this->get_filter_html( $attributes );
		}

		// Add portfolio items wrapper - match SCSS structure
		$output .= '<div class="wpdino-blocks_portfolio-block_items-list">';

		// Loop through posts
		while ( $query->have_posts() ) {
			$query->the_post();
			$output .= $this->get_portfolio_item_html( $attributes );
		}

		$output .= '</div>'; // Close portfolio grid

		// Add pagination if enabled
		if ( $attributes['showPagination'] && $query->max_num_pages > 1 ) {
			$output .= $this->get_pagination_html( $query, $attributes );
		}

		// Add view all link if enabled
		if ( $attributes['showViewAll'] && ! empty( $attributes['viewAllLink'] ) ) {
			$output .= $this->get_view_all_html( $attributes );
		}

		$output .= '</div>'; // Close container

		// Reset post data
		wp_reset_postdata();

		return $output;
	}

	/**
	 * Get individual portfolio item HTML
	 *
	 * @param array $attributes The merged attributes
	 * @return string Portfolio item HTML
	 */
	private function get_portfolio_item_html( $attributes ) {
		$post_id = get_the_ID();
		$classes = array( 'wpdino-blocks_portfolio-block_item' );
		
		// Add categories as classes for filtering
		$terms = get_the_terms( $post_id, $this->taxonomies[0] );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$classes[] = 'portfolio-cat-' . $term->slug;
			}
		}

		if ( ! empty( $attributes['layout'] ) && 'masonry' === $attributes['layout'] ) {
			$classes[] = 'is-masonry-item';
		}

        // Overlay variant for grid layout or is-style-overlay
        // Only treat as overlay when the style explicitly requests overlay
        $is_overlay = ( isset( $attributes['style'] ) && in_array( $attributes['style'], array( 'overlay', 'style-overlay', 'is-style-overlay' ), true ) );
        if ( $is_overlay ) {
			$thumb_id = get_post_thumbnail_id( $post_id );
			if ( ! $thumb_id ) {
				return '';
			}
			$img_src = wp_get_attachment_image_src( $thumb_id, $attributes['imageSize'] );
			$bg_url  = $img_src ? $img_src[0] : '';
			$thumb_classes = array( 'wpdino-blocks_portfolio-block_item-thumbnail' );
			if ( ! empty( $attributes['lightbox'] ) ) {
				$thumb_classes[] = 'lightbox';
			}
			$output  = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
			$output .= '<div class="' . esc_attr( implode( ' ', $thumb_classes ) ) . '" style="background-image:url(' . esc_url( $bg_url ) . ');">';
			if ( ! empty( $attributes['lightbox'] ) ) {
				$output .= $this->get_lightbox_zoom_icon_html();
			}
			// Link wrapper according to lightbox setting
			if ( ! empty( $attributes['lightbox'] ) ) {
				$full_image = wp_get_attachment_image_src( $thumb_id, 'full' );
				$output .= '<a href="' . esc_url( $full_image[0] ) . '" class="portfolio-lightbox-link" data-glightbox aria-label="' . esc_attr( get_the_title() ) . '"></a>';
			} else {
				$output .= '<a href="' . esc_url( get_permalink() ) . '" aria-label="' . esc_attr( get_the_title() ) . '"></a>';
			}
			$output .= '<div class="wpdino-blocks_portfolio-block_item-overlay">';
			// Title
			if ( ! empty( $attributes['showTitle'] ) ) {
				$output .= '<h3 class="wpdino-blocks_portfolio-block_item-title">';
				$output .= '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
				$output .= '</h3>';
			}
			// Excerpt
			if ( ! empty( $attributes['showExcerpt'] ) ) {
				$excerpt = get_the_excerpt();
				if ( $excerpt ) {
					$output .= '<div class="wpdino-blocks_portfolio-block_item-excerpt">' . wp_kses_post( $excerpt ) . '</div>';
				}
			}
			// Read more
			if ( ! empty( $attributes['showReadMore'] ) ) {
				$read_more_label = ! empty( $attributes['readMoreLabel'] ) ? $attributes['readMoreLabel'] : esc_html__( 'View Project', 'dinofolio' );
				$output .= '<div class="wpdino-blocks_portfolio-block_item-button">';
				$output .= '<a href="' . esc_url( get_permalink() ) . '" class="wpz-portfolio-button__link">' . esc_html( $read_more_label ) . '</a>';
				$output .= '</div>';
			}
			$output .= '</div>'; // overlay
			$output .= '</div>'; // thumbnail with background
			$output .= '</div>'; // item
			return $output;
		}

		// Default (list/masonry) structure
		$output = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$output .= $this->get_portfolio_item_image( $attributes, $post_id );
		$output .= '<div class="wpdino-blocks_portfolio-block_item-details">';
		if ( $attributes['showTitle'] ) {
			$output .= '<h3 class="wpdino-blocks_portfolio-block_item-title">';
			$output .= '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
			$output .= '</h3>';
		}
		if ( $attributes['showCategories'] && $terms && ! is_wp_error( $terms ) ) {
			$output .= $this->get_portfolio_item_categories_html( $terms );
		}
		if ( $attributes['showExcerpt'] ) {
			$excerpt = get_the_excerpt();
			if ( $excerpt && ! empty( $attributes['layout'] ) && 'masonry' === $attributes['layout'] ) {
				$excerpt = wp_trim_words( $excerpt, 18, '…' );
			}
			if ( $excerpt ) {
				$output .= '<div class="wpdino-blocks_portfolio-block_item-excerpt">' . wp_kses_post( $excerpt ) . '</div>';
			}
		}
		if ( $attributes['showReadMore'] ) {
			$read_more_label = ! empty( $attributes['readMoreLabel'] ) ? $attributes['readMoreLabel'] : esc_html__( 'View Project', 'dinofolio' );
			$output .= '<div class="wpdino-blocks_portfolio-block_item-button">';
			$output .= '<a href="' . esc_url( get_permalink() ) . '" class="wpz-portfolio-button__link">' . esc_html( $read_more_label ) . '</a>';
			$output .= '</div>';
		}
		$output .= '</div>';
		$output .= '</div>';
		return $output;
	}

	/**
	 * SVG icon shown before portfolio category names.
	 *
	 * @return string
	 */
	private function get_category_icon_svg() {
		return '<svg class="dinofolio-category-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>';
	}

	/**
	 * Zoom icon overlay for lightbox thumbnails.
	 *
	 * @return string
	 */
	private function get_lightbox_zoom_icon_html() {
		$svg = '<svg class="dinofolio-zoom-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>';

		return '<span class="dinofolio-lightbox-zoom-icon">' . $svg . '</span>';
	}

	/**
	 * Build category list markup for a portfolio item.
	 *
	 * @param array $terms WP_Term objects.
	 * @return string
	 */
	private function get_portfolio_item_categories_html( $terms ) {
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$term_links = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$term_links[] = '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
		}

		if ( empty( $term_links ) ) {
			return '';
		}

		$output  = '<div class="wpdino-blocks_portfolio-block_item-categories">';
		$output .= $this->get_category_icon_svg();
		$output .= '<span class="wpdino-blocks_portfolio-block_item-categories-list">';
		$output .= implode( '<span class="wpdino-category-sep" aria-hidden="true">, </span>', $term_links );
		$output .= '</span>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Get portfolio item image HTML
	 *
	 * @param array $attributes The merged attributes
	 * @param int $post_id Post ID
	 * @return string Image HTML
	 */
	private function get_portfolio_item_image( $attributes, $post_id ) {
		
		$image_size = $attributes['imageSize'];
		if ( ! empty( $attributes['layout'] ) && 'masonry' === $attributes['layout'] ) {
			$image_size = 'large';
		}
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		
		if ( ! $thumbnail_id ) {
			return '';
		}

		$classes = array( 'wpdino-blocks_portfolio-block_item-thumbnail' );
		
		if ( $attributes['lightbox'] ) {
			$classes[] = 'lightbox';
		}

		$output = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		
		if ( $attributes['lightbox'] ) {
			$full_image = wp_get_attachment_image_src( $thumbnail_id, 'full' );
			$output .= '<a href="' . esc_url( $full_image[0] ) . '" class="portfolio-lightbox-link" data-glightbox>';
		} else {
			$output .= '<a href="' . esc_url( get_permalink() ) . '">';
		}
		
		$output .= wp_get_attachment_image( $thumbnail_id, $image_size, false, array(
			'class' => 'wpdino-blocks_portfolio-block_item-image',
			'alt'   => esc_attr( get_the_title() ),
		) );
		
		$output .= '</a>';

		if ( $attributes['lightbox'] ) {
			$output .= $this->get_lightbox_zoom_icon_html();
		}

		$output .= '</div>';
		
		return $output;
	}

	/**
	 * Get filter HTML
	 *
	 * @param array $attributes The merged attributes
	 * @return string Filter HTML
	 */
	private function get_filter_html( $attributes ) {
		
		$terms = get_terms( array(
			'taxonomy'   => $this->taxonomies[0],
			'hide_empty' => true,
		) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$output = '<div class="wpdino-blocks_portfolio-block_filter">';
		$output .= '<ul>';
		
		// All filter
		$output .= '<li class="current-cat"><a href="#" data-filter="*">' . esc_html__( 'All', 'dinofolio' ) . '</a></li>';
		
		// Category filters
		foreach ( $terms as $term ) {
			$output .= '<li><a href="#" data-filter=".portfolio-cat-' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</a></li>';
		}
		
		$output .= '</ul>';
		$output .= '</div>';
		
		return $output;
	}

	/**
	 * Get pagination HTML - simplified standard WordPress pagination
	 *
	 * @param WP_Query $query The portfolio query
	 * @param array $attributes The merged attributes
	 * @return string Pagination HTML
	 */
	private function get_pagination_html( $query, $attributes ) {
		
		$total_pages = $query->max_num_pages;

		if ( $total_pages <= 1 ) {
			return '';
		}

		// Get current page
		$current_page = 1;
		if ( get_query_var( 'paged' ) ) {
			$current_page = max( 1, get_query_var( 'paged' ) );
		} elseif ( get_query_var( 'page' ) ) {
			$current_page = max( 1, get_query_var( 'page' ) );
		} elseif ( isset( $_GET['pg'] ) && is_numeric( $_GET['pg'] ) ) {
			$current_page = max( 1, intval( $_GET['pg'] ) );
		}

		$output = '<div class="wpdino-portfolio-pagination">';
		
		// Build pagination for different contexts
		if ( is_home() || is_archive() || is_post_type_archive( $this->post_type ) ) {
			// For archive pages, use standard WordPress pagination
			$pagination_args = array(
				'total'     => $total_pages,
				'current'   => $current_page,
				'prev_text' => '<span class="pagination-prev">&laquo; ' . esc_html__( 'Previous', 'dinofolio' ) . '</span>',
				'next_text' => '<span class="pagination-next">' . esc_html__( 'Next', 'dinofolio' ) . ' &raquo;</span>',
				'mid_size'  => 2,
				'end_size'  => 1,
				'before_page_number' => '<span class="page-number">',
				'after_page_number'  => '</span>',
			);
		} else {
			// For shortcodes, build pagination manually to avoid URL issues
			$output .= '<nav class="pagination-wrapper" aria-label="' . esc_attr__( 'Portfolio pagination', 'dinofolio' ) . '">';
			$output .= '<div class="pagination-items">';
			
			// Previous link
			if ( $current_page > 1 ) {
				$prev_url = add_query_arg( 'pg', $current_page - 1 );
				$output .= '<a class="page-numbers" href="' . esc_url( $prev_url ) . '">';
				$output .= '<span class="pagination-prev">&laquo; ' . esc_html__( 'Previous', 'dinofolio' ) . '</span>';
				$output .= '</a>';
			}
			
			// Page numbers
			$start_page = max( 1, $current_page - 2 );
			$end_page = min( $total_pages, $current_page + 2 );
			
			// First page if not in range
			if ( $start_page > 1 ) {
				$page_url = ( $total_pages == 1 ) ? remove_query_arg( 'pg' ) : add_query_arg( 'pg', 1 );
				$output .= '<a class="page-numbers" href="' . esc_url( $page_url ) . '">';
				$output .= '<span class="page-number">1</span>';
				$output .= '</a>';
				
				if ( $start_page > 2 ) {
					$output .= '<span class="page-numbers dots">…</span>';
				}
			}
			
			// Page range
			for ( $i = $start_page; $i <= $end_page; $i++ ) {
				if ( $i == $current_page ) {
					$output .= '<span class="page-numbers current">';
					$output .= '<span class="page-number">' . $i . '</span>';
					$output .= '</span>';
				} else {
					$page_url = ( $i == 1 ) ? remove_query_arg( 'pg' ) : add_query_arg( 'pg', $i );
					$output .= '<a class="page-numbers" href="' . esc_url( $page_url ) . '">';
					$output .= '<span class="page-number">' . $i . '</span>';
					$output .= '</a>';
				}
			}
			
			// Last page if not in range
			if ( $end_page < $total_pages ) {
				if ( $end_page < $total_pages - 1 ) {
					$output .= '<span class="page-numbers dots">…</span>';
				}
				
				$last_url = add_query_arg( 'pg', $total_pages );
				$output .= '<a class="page-numbers" href="' . esc_url( $last_url ) . '">';
				$output .= '<span class="page-number">' . $total_pages . '</span>';
				$output .= '</a>';
			}
			
			// Next link
			if ( $current_page < $total_pages ) {
				$next_url = add_query_arg( 'pg', $current_page + 1 );
				$output .= '<a class="page-numbers" href="' . esc_url( $next_url ) . '">';
				$output .= '<span class="pagination-next">' . esc_html__( 'Next', 'dinofolio' ) . ' &raquo;</span>';
				$output .= '</a>';
			}
			
			$output .= '</div>';
			$output .= '</nav>';
			$output .= '</div>';
			
			return $output;
		}

		$pagination_links = paginate_links( $pagination_args );
		
		if ( $pagination_links ) {
			$output .= '<nav class="pagination-wrapper" aria-label="' . esc_attr__( 'Portfolio pagination', 'dinofolio' ) . '">';
			$output .= $pagination_links;
			$output .= '</nav>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get view all link HTML
	 *
	 * @param array $attributes The merged attributes
	 * @return string View all HTML
	 */
	private function get_view_all_html( $attributes ) {
		
		$output = '<div class="wpdino-portfolio-view-all">';
		$output .= '<a href="' . esc_url( $attributes['viewAllLink'] ) . '" class="wpdino-view-all-btn wpz-portfolio-button__link">';
		$output .= esc_html( $attributes['viewAllText'] );
		$output .= '</a>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Get no posts message
	 *
	 * @param array $attributes The merged attributes
	 * @return string No posts message HTML
	 */
	private function get_no_posts_message( $attributes ) {
		
		$message = apply_filters( 'wpdino_portfolio_no_posts_message', esc_html__( 'No portfolio items found.', 'dinofolio' ), $attributes );
		
		return '<div class="wpdino-portfolio-no-posts">' . $message . '</div>';
	}

	/**
	 * Get portfolio categories for filtering
	 *
	 * @return array Categories data
	 */
	public function get_portfolio_categories() {
		
		$terms = get_terms( array(
			'taxonomy'   => $this->taxonomies[0],
			'hide_empty' => true,
		) );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		return $terms;
	}

	/**
	 * Get portfolio posts count
	 *
	 * @param array $attributes Query attributes
	 * @return int Posts count
	 */
	public function get_portfolio_count( $attributes = array() ) {
		
		$query_args = $this->build_query( $attributes );
		$query_args['fields'] = 'ids';
		$query_args['posts_per_page'] = -1;
		
		$query = new WP_Query( $query_args );
		
		return $query->found_posts;
	}

	/**
	 * Shortcode handler for [dinofolio] shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @param string $content Shortcode content (unused)
	 * @return string Portfolio HTML
	 */
	public function shortcode_handler( $atts = array(), $content = '' ) {
		
		// Parse shortcode attributes
		$attributes = shortcode_atts( array(
			'layout'         => 'grid',
			'columns'        => 3,
			'posts_to_show'  => 12,
			'show_excerpt'   => 'true',
			'show_read_more' => 'true', 
			'image_size'     => 'large',
			'order_by'       => 'date',
			'order'          => 'desc',
			'categories'     => '',
			'show_filter'    => 'false',
			'show_view_all'  => 'false',
			'view_all_text'  => 'View All',
			'view_all_link'  => '',
			'class_name'     => '',
			'show_pagination' => 'true',
			'show_title'     => 'true',
			'show_meta'      => 'true',
			'show_categories' => 'true',
			'lightbox'       => 'true',
			'hover_effect'   => '',
			'style'          => '',
		), $atts );

		// Convert shortcode attributes to block-style attributes
		$block_attributes = array(
			'layout'         => sanitize_text_field( $attributes['layout'] ),
			'columns'        => intval( $attributes['columns'] ),
			'postsToShow'    => intval( $attributes['posts_to_show'] ),
			'showExcerpt'    => filter_var( $attributes['show_excerpt'], FILTER_VALIDATE_BOOLEAN ),
			'showReadMore'   => filter_var( $attributes['show_read_more'], FILTER_VALIDATE_BOOLEAN ),
			'imageSize'      => sanitize_text_field( $attributes['image_size'] ),
			'orderBy'        => sanitize_text_field( $attributes['order_by'] ),
			'order'          => sanitize_text_field( $attributes['order'] ),
			'showFilter'     => filter_var( $attributes['show_filter'], FILTER_VALIDATE_BOOLEAN ),
			'showViewAll'    => filter_var( $attributes['show_view_all'], FILTER_VALIDATE_BOOLEAN ),
			'viewAllText'    => sanitize_text_field( $attributes['view_all_text'] ),
			'viewAllLink'    => esc_url_raw( $attributes['view_all_link'] ),
			'className'      => sanitize_text_field( $attributes['class_name'] ),
			'showPagination' => filter_var( $attributes['show_pagination'], FILTER_VALIDATE_BOOLEAN ),
			'showTitle'      => filter_var( $attributes['show_title'], FILTER_VALIDATE_BOOLEAN ),
			'showMeta'       => filter_var( $attributes['show_meta'], FILTER_VALIDATE_BOOLEAN ),
			'showCategories' => filter_var( $attributes['show_categories'], FILTER_VALIDATE_BOOLEAN ),
			'lightbox'       => filter_var( $attributes['lightbox'], FILTER_VALIDATE_BOOLEAN ),
		);

		// Handle categories (convert comma-separated string to array)
		if ( ! empty( $attributes['categories'] ) ) {
			$category_slugs = array_map( 'trim', explode( ',', $attributes['categories'] ) );
			$category_ids = array();
			
			foreach ( $category_slugs as $slug ) {
				$term = get_term_by( 'slug', $slug, $this->taxonomies[0] );
				if ( $term && ! is_wp_error( $term ) ) {
					$category_ids[] = $term->term_id;
				}
			}
			
			$block_attributes['categories'] = $category_ids;
		}

		// Handle custom hover effect and style if provided
		if ( ! empty( $attributes['hover_effect'] ) ) {
			$block_attributes['hoverEffect'] = sanitize_text_field( $attributes['hover_effect'] );
		}
		
		if ( ! empty( $attributes['style'] ) ) {
			$block_attributes['style'] = sanitize_text_field( $attributes['style'] );
		}

		// Force enqueue styles for shortcode usage
		$this->enqueue_listing_assets();

		// Render the portfolio
		return $this->render_portfolio_listing( $block_attributes );
	}

	/**
	 * Enqueue frontend assets only when needed
	 */
	public function enqueue_frontend_assets() {
		if ( $this->should_enqueue_assets() ) {
			$this->enqueue_listing_assets();
		}
	}

	/**
	 * Check if we should enqueue assets
	 * Note: Blocks handle their own assets, this is only for shortcodes and archives
	 */
	private function should_enqueue_assets() {
		global $post;
		
		// Always enqueue on portfolio archive pages
		if ( is_post_type_archive( $this->post_type ) || is_tax( $this->taxonomies ) ) {
			return true;
		}
		
		// Enqueue on single portfolio pages
		if ( is_singular( $this->post_type ) ) {
			return true;
		}
		
		// Check if current post has portfolio shortcodes (blocks handle their own assets)
		if ( $post ) {
			if ( has_shortcode( $post->post_content, 'dinofolio' ) || has_shortcode( $post->post_content, 'dinofolio_portfolio' ) ) {
				return true;
			}

			if ( function_exists( 'has_block' ) && ( has_block( 'dinofolio/portfolio', $post ) || has_block( 'wpdino-blocks/portfolio', $post ) ) ) {
				return true;
			}
		}

		// Elementor documents with the portfolio widget
		if ( $post && class_exists( '\Elementor\Plugin' ) ) {
			$document = \Elementor\Plugin::$instance->documents->get( $post->ID );
			if ( $document && method_exists( $document, 'is_built_with_elementor' ) && $document->is_built_with_elementor() ) {
				$data = get_post_meta( $post->ID, '_elementor_data', true );
				if ( is_string( $data ) && false !== strpos( $data, 'dinofolio-portfolio' ) ) {
					return true;
				}
			}
		}
		
		// Allow themes/plugins to force enqueue
		return apply_filters( 'wpdino_portfolio_enqueue_assets', false );
	}




}