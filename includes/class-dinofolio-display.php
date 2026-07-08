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
	 * Whether the current request rendered a listing with lightbox enabled.
	 *
	 * @var bool
	 */
	private static $needs_lightbox = false;

	/**
	 * Listing script dependencies flagged during render (isotope, dinofolio).
	 *
	 * @var array<string, bool>
	 */
	private static $listing_script_deps = array();

	/**
	 * Whether a listing with AJAX load more was rendered.
	 *
	 * @var bool
	 */
	private static $listing_load_more_needed = false;

	/**
	 * Gallery group id for the listing currently being rendered.
	 *
	 * @var string
	 */
	private static $listing_gallery_id = '';

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

		add_action( 'wp_ajax_dinofolio_load_more', array( $this, 'ajax_load_more' ) );
		add_action( 'wp_ajax_nopriv_dinofolio_load_more', array( $this, 'ajax_load_more' ) );

		add_action( 'init', array( $this, 'register_listing_assets' ) );
		add_action( 'pre_get_posts', array( $this, 'adjust_archive_main_query' ) );
		add_filter( 'pre_handle_404', array( $this, 'prevent_archive_pagination_404' ), 10, 2 );

		// Enqueue frontend assets only when needed
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'wp_footer', array( $this, 'enqueue_lightbox_assets' ), 5 );
		add_action( 'wp_footer', array( $this, 'enqueue_listing_script_assets' ), 5 );
	}

	/**
	 * GLightbox style handle.
	 *
	 * @return string
	 */
	public static function get_glightbox_style_handle() {
		return 'dinofolio-glightbox';
	}

	/**
	 * GLightbox script handle.
	 *
	 * @return string
	 */
	public static function get_glightbox_script_handle() {
		return 'dinofolio-glightbox';
	}

	/**
	 * Plyr style handle (used by GLightbox for video slides).
	 *
	 * @return string
	 */
	public static function get_plyr_style_handle() {
		return 'dinofolio-plyr';
	}

	/**
	 * Plyr script handle (used by GLightbox for video slides).
	 *
	 * @return string
	 */
	public static function get_plyr_script_handle() {
		return 'dinofolio-plyr';
	}

	/**
	 * Portfolio lightbox initializer script handle.
	 *
	 * @return string
	 */
	public static function get_portfolio_lightbox_script_handle() {
		return 'dinofolio-portfolio-lightbox';
	}

	/**
	 * Main portfolio listing script handle (Isotope, parallax).
	 *
	 * @return string
	 */
	public static function get_listing_script_handle() {
		return 'dinofolio';
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
	 * Sanitize rendered portfolio HTML returned by callbacks.
	 *
	 * @param string $html HTML markup.
	 * @return string
	 */
	public static function sanitize_rendered_html( $html ) {
		$html = is_string( $html ) ? $html : '';

		if ( '' === $html ) {
			return '';
		}

		return wp_kses( $html, self::get_rendered_html_allowlist() );
	}

	/**
	 * Allowed HTML tags for rendered listing output.
	 *
	 * @return array<string, array<string, bool>>
	 */
	private static function get_rendered_html_allowlist() {
		static $allowed = null;

		if ( null !== $allowed ) {
			return $allowed;
		}

		$allowed = wp_kses_allowed_html( 'post' );

		// WordPress post KSES strips SVG by default; listing UI uses inline icons.
		$allowed['svg'] = array(
			'class'            => true,
			'xmlns'            => true,
			'width'            => true,
			'height'           => true,
			'viewBox'          => true,
			'fill'             => true,
			'stroke'           => true,
			'stroke-width'     => true,
			'stroke-linecap'   => true,
			'stroke-linejoin'  => true,
			'aria-hidden'      => true,
			'focusable'        => true,
			'role'             => true,
			'preserveAspectRatio' => true,
		);
		$allowed['path'] = array(
			'd'               => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
		);
		$allowed['circle'] = array(
			'cx'           => true,
			'cy'           => true,
			'r'            => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
		);
		$allowed['line'] = array(
			'x1'           => true,
			'y1'           => true,
			'x2'           => true,
			'y2'           => true,
			'stroke'       => true,
			'stroke-width' => true,
		);
		$allowed['polyline'] = array(
			'points'           => true,
			'fill'             => true,
			'stroke'           => true,
			'stroke-width'     => true,
			'stroke-linecap'   => true,
			'stroke-linejoin'  => true,
		);
		$allowed['polygon'] = array(
			'points' => true,
			'fill'   => true,
			'stroke' => true,
		);

		$common_data_tags = array(
			'a',
			'button',
			'div',
			'figure',
			'img',
			'input',
			'label',
			'li',
			'nav',
			'section',
			'span',
			'ul',
		);

		foreach ( $common_data_tags as $tag ) {
			if ( ! isset( $allowed[ $tag ] ) || ! is_array( $allowed[ $tag ] ) ) {
				$allowed[ $tag ] = array();
			}

			$allowed[ $tag ]['data-*'] = true;
			$allowed[ $tag ]['aria-*'] = true;
		}

		return apply_filters( 'dinofolio_rendered_html_allowlist', $allowed );
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

		wp_register_style(
			self::get_glightbox_style_handle(),
			DINOFOLIO_URL . 'assets/vendor/glightbox/glightbox.min.css',
			array(),
			'3.3.0'
		);

		wp_register_style(
			self::get_plyr_style_handle(),
			DINOFOLIO_URL . 'assets/vendor/plyr/plyr.min.css',
			array(),
			'3.7.8'
		);

		wp_register_script(
			self::get_plyr_script_handle(),
			DINOFOLIO_URL . 'assets/vendor/plyr/plyr.min.js',
			array(),
			'3.7.8',
			true
		);

		wp_register_script(
			self::get_glightbox_script_handle(),
			DINOFOLIO_URL . 'assets/vendor/glightbox/glightbox.min.js',
			array( self::get_plyr_script_handle() ),
			'3.3.0',
			true
		);

		wp_register_script(
			self::get_portfolio_lightbox_script_handle(),
			DINOFOLIO_URL . 'assets/js/portfolio-lightbox.js',
			array( self::get_glightbox_script_handle(), self::get_plyr_script_handle() ),
			DINOFOLIO_VERSION,
			true
		);

		wp_register_script(
			'dinofolio-imagesloaded',
			DINOFOLIO_URL . 'assets/vendor/imagesloaded/imagesloaded.pkgd.min.js',
			array(),
			'5.0.0',
			true
		);

		wp_register_script(
			'dinofolio-isotope',
			DINOFOLIO_URL . 'assets/vendor/isotope/isotope.pkgd.min.js',
			array( 'dinofolio-imagesloaded' ),
			'3.0.6',
			true
		);

		wp_register_script(
			self::get_listing_script_handle(),
			DINOFOLIO_URL . 'assets/js/dinofolio.min.js',
			array(),
			DINOFOLIO_VERSION,
			true
		);
	}

	/**
	 * Mark a listing script dependency for this request.
	 *
	 * @param string $dep Dependency key: isotope or dinofolio.
	 * @return void
	 */
	public static function flag_listing_script( $dep ) {
		self::$listing_script_deps[ $dep ] = true;
	}

	/**
	 * Mark that AJAX load more should load listing scripts and config.
	 *
	 * @return void
	 */
	public static function flag_listing_load_more() {
		self::$listing_load_more_needed = true;
		self::flag_listing_script( 'dinofolio' );
	}

	/**
	 * Mark that category filter / listing JS should load on this request.
	 *
	 * @return void
	 */
	public static function flag_category_filter_assets() {
		self::flag_listing_script( 'dinofolio' );
	}

	/**
	 * Enqueue listing scripts when Isotope, parallax, or filter is active.
	 *
	 * @return void
	 */
	public function enqueue_listing_script_assets() {
		if ( empty( self::$listing_script_deps ) ) {
			return;
		}

		if ( ! wp_script_is( self::get_listing_script_handle(), 'registered' ) ) {
			$this->register_listing_assets();
		}

		if ( self::is_block_editor_preview() ) {
			if ( ! empty( self::$listing_script_deps['dinofolio'] ) ) {
				wp_enqueue_script( self::get_listing_script_handle() );
			}
			return;
		}

		$deps = array();

		if ( ! empty( self::$listing_script_deps['isotope'] ) ) {
			wp_enqueue_script( 'dinofolio-isotope' );
			$deps[] = 'dinofolio-isotope';
		}

		wp_scripts()->add(
			self::get_listing_script_handle(),
			DINOFOLIO_URL . 'assets/js/dinofolio.min.js',
			$deps,
			DINOFOLIO_VERSION,
			true
		);

		wp_enqueue_script( self::get_listing_script_handle() );

		if ( self::$listing_load_more_needed ) {
			wp_localize_script(
				self::get_listing_script_handle(),
				'dinofolioListing',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'dinofolio_load_more' ),
					'i18n'    => array(
						'loading' => esc_html__( 'Loading...', 'dinofolio' ),
						'error'   => esc_html__( 'Unable to load more projects. Please try again.', 'dinofolio' ),
					),
				)
			);
		}
	}

	/**
	 * Mark that GLightbox assets should load on this request.
	 *
	 * @return void
	 */
	public static function flag_lightbox_assets() {
		self::$needs_lightbox = true;
	}

	/**
	 * Whether GLightbox should be enqueued for the current request.
	 *
	 * @return bool
	 */
	public static function needs_lightbox_assets() {
		return self::$needs_lightbox;
	}

	/**
	 * Enqueue GLightbox when a listing with lightbox was rendered (runs in footer).
	 *
	 * @return void
	 */
	public function enqueue_lightbox_assets() {
		if ( ! self::$needs_lightbox || self::is_block_editor_preview() ) {
			return;
		}

		if ( ! wp_style_is( self::get_glightbox_style_handle(), 'registered' ) ) {
			$this->register_listing_assets();
		}

		wp_enqueue_style( self::get_plyr_style_handle() );
		wp_enqueue_style( self::get_glightbox_style_handle() );
		wp_enqueue_script( self::get_plyr_script_handle() );
		wp_enqueue_script( self::get_glightbox_script_handle() );
		wp_enqueue_script( self::get_portfolio_lightbox_script_handle() );
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
	 * Whether the current request is Elementor's live preview iframe.
	 *
	 * @return bool
	 */
	public static function is_elementor_preview() {
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}

		$plugin = \Elementor\Plugin::$instance;

		if ( $plugin->preview && method_exists( $plugin->preview, 'is_preview_mode' ) && $plugin->preview->is_preview_mode() ) {
			return true;
		}

		return $plugin->editor && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode();
	}

	/**
	 * Resolve the current listing page from query vars or the pg URL parameter.
	 *
	 * @return int
	 */
	private function get_listing_current_page() {
		if ( get_query_var( 'paged' ) ) {
			return max( 1, (int) get_query_var( 'paged' ) );
		}

		if ( get_query_var( 'page' ) ) {
			return max( 1, (int) get_query_var( 'page' ) );
		}

		$page_param = filter_input( INPUT_GET, 'pg', FILTER_VALIDATE_INT );

		if ( false !== $page_param && $page_param > 0 ) {
			return max( 1, $page_param );
		}

		return 1;
	}

	/**
	 * Posts-per-page used on portfolio taxonomy and archive templates.
	 *
	 * @return int
	 */
	private function get_archive_posts_per_page() {
		$posts_per_page = (int) $this->settings->get_setting( 'taxonomy_posts_per_page', 12 );

		return max( 1, min( 100, $posts_per_page ) );
	}

	/**
	 * Apply taxonomy/archive listing settings to the main query so /page/N/ URLs resolve.
	 *
	 * The listing renderer runs a secondary WP_Query; if the main query keeps the site
	 * default posts_per_page, WordPress can 404 on valid pagination URLs.
	 *
	 * @param \WP_Query $query Main query.
	 * @return void
	 */
	public function adjust_archive_main_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$is_portfolio_tax     = $query->is_tax( $this->taxonomies );
		$is_portfolio_archive = $query->is_post_type_archive( $this->post_type );

		if ( ! $is_portfolio_tax && ! $is_portfolio_archive ) {
			return;
		}

		$query->set( 'posts_per_page', $this->get_archive_posts_per_page() );

		$order_by = sanitize_key( (string) $this->settings->get_setting( 'taxonomy_order_by', 'date' ) );
		$order    = strtoupper( sanitize_key( (string) $this->settings->get_setting( 'taxonomy_order', 'desc' ) ) );
		$order    = ( 'ASC' === $order ) ? 'ASC' : 'DESC';

		if ( 'menu_order' === $order_by ) {
			$query->set( 'orderby', 'menu_order date' );
			$query->set( 'order', 'ASC' );
			return;
		}

		$allowed_orderby = array( 'date', 'title', 'modified', 'rand', 'menu_order' );

		if ( in_array( $order_by, $allowed_orderby, true ) ) {
			$query->set( 'orderby', $order_by );
			$query->set( 'order', $order );
		}
	}

	/**
	 * Prevent false 404s on portfolio taxonomy/archive pagination pages.
	 *
	 * @param bool      $preempt  Whether to short-circuit 404 handling.
	 * @param \WP_Query $wp_query Main query.
	 * @return bool
	 */
	public function prevent_archive_pagination_404( $preempt, $wp_query ) {
		if ( $preempt || ! $wp_query->is_main_query() ) {
			return $preempt;
		}

		$paged = max( 1, (int) $wp_query->get( 'paged' ) );

		if ( $paged < 2 ) {
			return $preempt;
		}

		$is_portfolio_tax     = $wp_query->is_tax( $this->taxonomies );
		$is_portfolio_archive = $wp_query->is_post_type_archive( $this->post_type );

		if ( ! $is_portfolio_tax && ! $is_portfolio_archive ) {
			return $preempt;
		}

		$attributes = $is_portfolio_tax
			? $this->get_taxonomy_listing_attributes( $wp_query->get_queried_object() )
			: $this->get_archive_listing_attributes();

		if ( empty( $attributes ) ) {
			return $preempt;
		}

		$attributes['paged'] = $paged;
		$count_query           = new WP_Query(
			array_merge(
				$this->build_query( $attributes ),
				array(
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			)
		);

		if ( (int) $count_query->max_num_pages >= $paged ) {
			$wp_query->is_404 = false;
			status_header( 200 );
			return true;
		}

		return $preempt;
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

		if ( self::should_enqueue_listing_editor_styles() ) {
			wp_enqueue_style( 'dinofolio-portfolio-listing-editor' );
			$this->enqueue_editor_gallery_carousel_script();
		}
	}

	/**
	 * Enqueue listing carousel script in the block editor canvas.
	 *
	 * @return void
	 */
	public function enqueue_editor_gallery_carousel_script() {
		if ( ! wp_script_is( self::get_listing_script_handle(), 'registered' ) ) {
			$this->register_listing_assets();
		}

		wp_enqueue_script( self::get_listing_script_handle() );
	}

	/**
	 * Whether editor-only listing overrides should load.
	 *
	 * @return bool
	 */
	public static function should_enqueue_listing_editor_styles() {
		if ( self::is_block_editor_preview() ) {
			return true;
		}

		if ( self::is_elementor_preview() ) {
			return true;
		}

		if ( ! is_admin() ) {
			return false;
		}

		return function_exists( 'wp_should_load_block_editor_scripts_and_styles' )
			&& wp_should_load_block_editor_scripts_and_styles();
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

		/**
		 * Fires before a portfolio listing is rendered.
		 *
		 * @param array $attributes Merged listing attributes.
		 * @param array $extra_args Extra query arguments.
		 */
		do_action( 'dinofolio_before_render_listing', $attributes, $extra_args );
		
		// Build the query arguments
		$query_args = $this->build_query( $attributes, $extra_args );
		
		// Execute the query
		$portfolio_query = new WP_Query( $query_args );

		/**
		 * Allow extensions to render listing output before the default markup.
		 *
		 * @param string|null $output          Pre-rendered HTML or null to use default.
		 * @param WP_Query    $portfolio_query Portfolio query instance.
		 * @param array       $attributes      Merged listing attributes.
		 */
		$pre_output = apply_filters( 'dinofolio_pre_render_listing', null, $portfolio_query, $attributes );
		if ( null !== $pre_output ) {
			$output = $pre_output;
		} else {
			// Generate and return the HTML output
			$output = $this->generate_portfolio_html( $portfolio_query, $attributes );
		}

		/**
		 * Filter portfolio listing render output.
		 *
		 * @param string   $output          Rendered HTML.
		 * @param array    $attributes      Merged listing attributes.
		 * @param WP_Query $portfolio_query Portfolio query instance.
		 */
		$output = apply_filters( 'dinofolio_listing_output', $output, $attributes, $portfolio_query );

		return self::sanitize_rendered_html( $output );
	}

	/**
	 * Build listing attributes for portfolio taxonomy archive pages from settings.
	 *
	 * @param \WP_Term|null $term Queried taxonomy term. Defaults to get_queried_object().
	 * @return array
	 */
	public function get_taxonomy_listing_attributes( $term = null ) {
		if ( ! $term instanceof \WP_Term ) {
			$term = get_queried_object();
		}

		if ( ! $term instanceof \WP_Term ) {
			return array();
		}

		$attributes = array(
			'layout'          => $this->settings->get_setting( 'taxonomy_layout', 'grid' ),
			'columns'         => (int) $this->settings->get_setting( 'taxonomy_columns', 3 ),
			'postsToShow'     => (int) $this->settings->get_setting( 'taxonomy_posts_per_page', 12 ),
			'imageSize'       => $this->settings->get_setting( 'taxonomy_image_size', 'large' ),
			'showTitle'       => (bool) $this->settings->get_setting( 'taxonomy_show_title', true ),
			'showCategories'  => (bool) $this->settings->get_setting( 'taxonomy_show_categories', true ),
			'showExcerpt'     => (bool) $this->settings->get_setting( 'taxonomy_show_excerpt', true ),
			'excerptLength'   => (int) $this->settings->get_setting( 'taxonomy_excerpt_length', 120 ),
			'showReadMore'    => (bool) $this->settings->get_setting( 'taxonomy_show_read_more', true ),
			'readMoreLabel'   => $this->settings->get_setting( 'taxonomy_read_more_label', esc_html__( 'View Project', 'dinofolio' ) ),
			'readMoreAlign'   => $this->settings->get_setting( 'taxonomy_read_more_align', 'right' ),
			'lightbox'        => (bool) $this->settings->get_setting( 'taxonomy_lightbox', true ),
			'enableParallax'  => (bool) $this->settings->get_setting( 'taxonomy_enable_parallax', false ),
			'paginationMode'  => (bool) $this->settings->get_setting( 'taxonomy_show_pagination', true ) ? 'pagination' : 'none',
			'showViewAll'     => false,
			'viewAllText'     => '',
			'viewAllLink'     => '',
			'showFilter'      => false,
			'showFilterCount' => false,
			'orderBy'         => $this->settings->get_setting( 'taxonomy_order_by', 'date' ),
			'order'           => $this->settings->get_setting( 'taxonomy_order', 'desc' ),
			'style'           => $this->settings->get_setting( 'taxonomy_style', 'standard' ),
			'colorScheme'     => 'light',
			'hoverEffect'     => 'zoom',
			'accentColor'     => $this->settings->get_setting( 'taxonomy_accent_color', '#1a8960' ),
			'hoverColor'      => $this->settings->get_setting( 'taxonomy_hover_color', '' ),
			'buttonTextColor' => $this->settings->get_setting( 'taxonomy_button_text_color', '' ),
			'mutedColor'      => $this->settings->get_setting( 'taxonomy_muted_color', '' ),
			'gap'             => (int) $this->settings->get_setting( 'taxonomy_gap', 24 ),
			'radius'          => (int) $this->settings->get_setting( 'taxonomy_radius', 10 ),
		);

		if ( $term->taxonomy === $this->taxonomies[0] ) {
			$attributes['categories'] = array( (int) $term->term_id );
		} elseif ( isset( $this->taxonomies[1] ) && $term->taxonomy === $this->taxonomies[1] ) {
			$attributes['tags'] = array( (int) $term->term_id );
		}

		return apply_filters( 'dinofolio_taxonomy_listing_attributes', $attributes, $term );
	}

	/**
	 * Build listing attributes for the portfolio post type archive.
	 *
	 * @return array
	 */
	public function get_archive_listing_attributes() {
		$attributes = array(
			'layout'          => $this->settings->get_setting( 'taxonomy_layout', 'grid' ),
			'columns'         => (int) $this->settings->get_setting( 'taxonomy_columns', 3 ),
			'postsToShow'     => (int) $this->settings->get_setting( 'taxonomy_posts_per_page', 12 ),
			'imageSize'       => $this->settings->get_setting( 'taxonomy_image_size', 'large' ),
			'showTitle'       => (bool) $this->settings->get_setting( 'taxonomy_show_title', true ),
			'showCategories'  => (bool) $this->settings->get_setting( 'taxonomy_show_categories', true ),
			'showExcerpt'     => (bool) $this->settings->get_setting( 'taxonomy_show_excerpt', true ),
			'excerptLength'   => (int) $this->settings->get_setting( 'taxonomy_excerpt_length', 120 ),
			'showReadMore'    => (bool) $this->settings->get_setting( 'taxonomy_show_read_more', true ),
			'readMoreLabel'   => $this->settings->get_setting( 'taxonomy_read_more_label', esc_html__( 'View Project', 'dinofolio' ) ),
			'readMoreAlign'   => $this->settings->get_setting( 'taxonomy_read_more_align', 'right' ),
			'lightbox'        => (bool) $this->settings->get_setting( 'taxonomy_lightbox', true ),
			'enableParallax'  => (bool) $this->settings->get_setting( 'taxonomy_enable_parallax', false ),
			'paginationMode'  => (bool) $this->settings->get_setting( 'taxonomy_show_pagination', true ) ? 'pagination' : 'none',
			'showViewAll'     => false,
			'viewAllText'     => '',
			'viewAllLink'     => '',
			'showFilter'      => false,
			'showFilterCount' => false,
			'orderBy'         => $this->settings->get_setting( 'taxonomy_order_by', 'date' ),
			'order'           => $this->settings->get_setting( 'taxonomy_order', 'desc' ),
			'style'           => $this->settings->get_setting( 'taxonomy_style', 'standard' ),
			'colorScheme'     => 'light',
			'hoverEffect'     => 'zoom',
			'accentColor'     => $this->settings->get_setting( 'taxonomy_accent_color', '#1a8960' ),
			'hoverColor'      => $this->settings->get_setting( 'taxonomy_hover_color', '' ),
			'buttonTextColor' => $this->settings->get_setting( 'taxonomy_button_text_color', '' ),
			'mutedColor'      => $this->settings->get_setting( 'taxonomy_muted_color', '' ),
			'gap'             => (int) $this->settings->get_setting( 'taxonomy_gap', 24 ),
			'radius'          => (int) $this->settings->get_setting( 'taxonomy_radius', 10 ),
		);

		return apply_filters( 'dinofolio_archive_listing_attributes', $attributes );
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
			$args['paged'] = $this->get_listing_current_page();
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

		if ( class_exists( '\DinoFolio\Components' ) && class_exists( '\DinoFolio\Util' ) ) {
			$component = \DinoFolio\Components::instance()->get( 'portfolio' );

			if ( $component ) {
				$normalized = \DinoFolio\Util::normalize_attribute_keys( $normalized, $component->get_params() );
			}
		}
		// Map legacy Gutenberg block styles (is-style-*) from className.
		if ( ! empty( $normalized['className'] ) && is_string( $normalized['className'] ) ) {
			if ( strpos( $normalized['className'], 'is-style-grid' ) !== false ) {
				$normalized['layout'] = 'grid';
			}
			if ( strpos( $normalized['className'], 'is-style-overlay' ) !== false ) {
				$normalized['style'] = 'overlay';
			}
			if ( strpos( $normalized['className'], 'is-style-masonry' ) !== false ) {
				$normalized['layout'] = 'masonry';
			}
			if ( strpos( $normalized['className'], 'is-style-carousel' ) !== false ) {
				$normalized['layout'] = 'carousel';
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

		if ( isset( $normalized['categories'] ) && class_exists( '\DinoFolio\Util' ) ) {
			$normalized['categories'] = \DinoFolio\Util::sanitize_taxonomy_term_ids( $normalized['categories'] );
		}

		if ( isset( $normalized['tags'] ) && class_exists( '\DinoFolio\Util' ) ) {
			$normalized['tags'] = \DinoFolio\Util::sanitize_taxonomy_term_ids( $normalized['tags'] );
		}

		if ( isset( $normalized['showPagination'] ) && ! isset( $normalized['paginationMode'] ) ) {
			$normalized['paginationMode'] = $normalized['showPagination'] ? 'pagination' : 'none';
		}

		if ( isset( $normalized['pagination_type'] ) && ! isset( $normalized['paginationMode'] ) ) {
			$normalized['paginationMode'] = sanitize_key( (string) $normalized['pagination_type'] );
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
			'excerptLength'  => 120,
			'showReadMore'   => true,
			'readMoreLabel'  => esc_html__( 'View Project', 'dinofolio' ),
			'readMoreAlign'  => 'right',
			'imageSize'      => $this->settings->get_setting( 'image_size', 'large' ),
			'orderBy'        => 'date',
			'order'          => 'desc',
			'categories'     => array(),
			'tags'           => array(),
			'showFilter'      => false,
			'showFilterCount' => false,
			'filterDynamic'   => false,
			'showViewAll'    => false,
			'viewAllText'    => esc_html__( 'View All', 'dinofolio' ),
			'viewAllLink'    => '',
			'className'      => '',
			'paginationMode' => in_array( 'pagination', $enabled_features, true ) ? 'pagination' : 'none',
			'loadMoreLabel'  => esc_html__( 'Load More', 'dinofolio' ),
			'loadMoreTrigger' => 'click',
			'showTitle'      => true,
			'showMeta'       => true,
			'showCategories' => true,
			'lightbox'        => $this->settings->get_setting( 'enable_lightbox', true ),
			'hoverEffect'     => $this->settings->get_setting( 'hover_effect', 'zoom' ),
			'style'           => $this->settings->get_setting( 'portfolio_style', 'standard' ),
			'colorScheme'     => 'light',
			'accentColor'     => '#1a8960',
			'hoverColor'      => '',
			'buttonTextColor' => '',
			'mutedColor'      => '',
			'gap'                 => 24,
			'radius'              => 10,
			'enableParallax'      => false,
		);

		// Merge and sanitize
		$merged = wp_parse_args( $normalized, $block_defaults );

		// Type casting and validation
		$merged['columns']      = max( 2, min( 4, intval( $merged['columns'] ) ) );
		$merged['postsToShow']  = max( 1, min( 100, intval( $merged['postsToShow'] ) ) );
		$merged['showExcerpt']  = (bool) $merged['showExcerpt'];
		$merged['excerptLength'] = $this->normalize_excerpt_length(
			isset( $merged['excerptLength'] ) ? $merged['excerptLength'] : 120
		);
		$merged['showReadMore'] = (bool) $merged['showReadMore'];
		$merged['readMoreAlign'] = $this->normalize_read_more_align(
			isset( $merged['readMoreAlign'] ) ? $merged['readMoreAlign'] : 'right'
		);
		$merged['showTitle']    = (bool) $merged['showTitle'];
		$merged['showCategories'] = (bool) $merged['showCategories'];
		$merged['showFilter']      = (bool) $merged['showFilter'];
		$merged['showFilterCount'] = (bool) $merged['showFilterCount'];
		$merged['showViewAll']  = (bool) $merged['showViewAll'];
		$merged['paginationMode'] = $this->normalize_pagination_mode( isset( $merged['paginationMode'] ) ? $merged['paginationMode'] : 'pagination' );
		$merged['loadMoreLabel'] = ! empty( $merged['loadMoreLabel'] )
			? sanitize_text_field( $merged['loadMoreLabel'] )
			: esc_html__( 'Load More', 'dinofolio' );
		$merged['loadMoreTrigger'] = $this->normalize_load_more_trigger(
			isset( $merged['loadMoreTrigger'] ) ? $merged['loadMoreTrigger'] : 'click'
		);
		$merged['showPagination'] = ( 'pagination' === $merged['paginationMode'] );
		$merged['lightbox']            = (bool) $merged['lightbox'];
		$merged['enableParallax']      = (bool) $merged['enableParallax'];

		// Legacy: overlay/list were separate layouts — map to style + grid only when style was not set explicitly.
		if ( 'overlay' === $merged['layout'] || 'list' === $merged['layout'] ) {
			$merged['layout'] = 'grid';

			if ( ! array_key_exists( 'style', $attributes ) && ! array_key_exists( 'style', $normalized ) ) {
				$merged['style'] = 'overlay';
			}
		}

		if ( 'classic' === $merged['style'] ) {
			$merged['style'] = 'standard';
		}

		// Validate layout
		$valid_layouts = apply_filters(
			'dinofolio_layouts',
			array(
				'grid'    => array(
					'label' => __( 'Grid', 'dinofolio' ),
					'pro'   => false,
				),
				'masonry' => array(
					'label' => __( 'Masonry', 'dinofolio' ),
					'pro'   => false,
				),
			)
		);
		$valid_layout_keys = is_array( $valid_layouts ) ? array_keys( $valid_layouts ) : array( 'grid', 'masonry' );
		if ( ! in_array( $merged['layout'], $valid_layout_keys, true ) ) {
			$merged['layout'] = 'grid';
		}

		$valid_styles = array( 'standard', 'overlay' );
		if ( ! in_array( $merged['style'], $valid_styles, true ) ) {
			$merged['style'] = 'standard';
		}

		$valid_color_schemes = array( 'light', 'dark' );
		if ( ! isset( $merged['colorScheme'] ) || ! in_array( $merged['colorScheme'], $valid_color_schemes, true ) ) {
			$merged['colorScheme'] = 'light';
		}

		$valid_hover_effects = array( 'zoom' );
		if ( ! in_array( $merged['hoverEffect'], $valid_hover_effects, true ) ) {
			$merged['hoverEffect'] = 'zoom';
		}

		$merged['gap']    = max( 0, min( 80, (int) $merged['gap'] ) );
		$merged['radius'] = max( 0, min( 40, (int) $merged['radius'] ) );

		foreach ( array( 'accentColor', 'hoverColor', 'buttonTextColor', 'mutedColor' ) as $color_key ) {
			if ( ! empty( $merged[ $color_key ] ) ) {
				$sanitized_color = sanitize_hex_color( $merged[ $color_key ] );
				$merged[ $color_key ] = $sanitized_color ? $sanitized_color : '';
			} else {
				$merged[ $color_key ] = '';
			}
		}

		// Validate order
		$merged['order'] = in_array( strtoupper( $merged['order'] ), array( 'ASC', 'DESC' ) ) ? $merged['order'] : 'desc';

		/**
		 * Filter merged portfolio listing attributes.
		 *
		 * @param array $merged     Sanitized listing attributes.
		 * @param array $attributes Raw incoming attributes.
		 */
		$merged = apply_filters( 'dinofolio_listing_attributes', $merged, $attributes );

		return apply_filters( 'wpdino_portfolio_merged_attributes', $merged, $attributes );
	}

	/**
	 * Build CSS custom properties for a listing instance.
	 *
	 * @param array $attributes Merged listing attributes.
	 * @return array
	 */
	private function get_listing_css_vars( $attributes ) {
		$vars = array();

		$color_map = array(
			'accentColor'     => '--dinofolio-accent',
			'hoverColor'      => '--dinofolio-hover',
			'buttonTextColor' => '--dinofolio-button-text',
			'mutedColor'      => '--dinofolio-muted',
		);

		foreach ( $color_map as $attribute_key => $css_var ) {
			if ( empty( $attributes[ $attribute_key ] ) ) {
				continue;
			}

			$color = sanitize_hex_color( $attributes[ $attribute_key ] );
			if ( $color ) {
				$vars[ $css_var ] = $color;
			}
		}

		if ( array_key_exists( 'gap', $attributes ) ) {
			$vars['--dinofolio-gap'] = max( 0, min( 80, (int) $attributes['gap'] ) ) . 'px';
		}

		if ( ! empty( $attributes['columns'] ) ) {
			$vars['--dinofolio-columns'] = max( 1, min( 6, (int) $attributes['columns'] ) );
		}

		if ( isset( $attributes['radius'] ) && '' !== $attributes['radius'] && null !== $attributes['radius'] ) {
			$vars['--dinofolio-radius'] = max( 0, min( 40, (int) $attributes['radius'] ) ) . 'px';
		}

		return apply_filters( 'dinofolio_listing_css_vars', $vars, $attributes );
	}

	/**
	 * Inline style attribute for listing CSS variables.
	 *
	 * @param array $attributes Merged listing attributes.
	 * @return string
	 */
	private function get_listing_inline_style_attr( $attributes ) {
		$vars = $this->get_listing_css_vars( $attributes );

		if ( empty( $vars ) ) {
			return '';
		}

		$parts = array();

		foreach ( $vars as $property => $value ) {
			$parts[] = esc_attr( $property ) . ':' . esc_attr( $value );
		}

		return ' style="' . esc_attr( implode( ';', $parts ) ) . '"';
	}

	/**
	 * Frontend JS config for a listing instance.
	 *
	 * @param array $attributes Merged listing attributes.
	 * @return array
	 */
	private function get_listing_js_config( $attributes ) {
		$pagination_mode = isset( $attributes['paginationMode'] ) ? $attributes['paginationMode'] : 'none';
		// Isotope is only required when the category filter reflows items (grid or masonry).
		$uses_isotope    = ! empty( $attributes['showFilter'] );
		$needs_script    = $uses_isotope
			|| ! empty( $attributes['enableParallax'] )
			|| ! empty( $attributes['showFilter'] )
			|| 'load_more' === $pagination_mode;

		if ( ! $needs_script ) {
			return array();
		}

		$config = array(
			'layout'          => $attributes['layout'],
			'columns'         => (int) $attributes['columns'],
			'gap'             => (int) $attributes['gap'],
			'filter'          => (bool) $attributes['showFilter'],
			'showFilterCount' => ! empty( $attributes['showFilterCount'] ),
			'isotope'         => $uses_isotope,
			'parallax'        => ! empty( $attributes['enableParallax'] ),
		);

		if ( 'load_more' === $pagination_mode ) {
			$config['loadMore']        = true;
			$config['loadMoreTrigger'] = $this->normalize_load_more_trigger(
				isset( $attributes['loadMoreTrigger'] ) ? $attributes['loadMoreTrigger'] : 'click'
			);
			$config['query']           = $this->get_load_more_query_payload( $attributes );
			self::flag_listing_load_more();
		}

		/**
		 * Filter frontend listing JavaScript config.
		 *
		 * @param array $config     Listing JS config.
		 * @param array $attributes Merged listing attributes.
		 */
		return apply_filters( 'dinofolio_listing_js_config', $config, $attributes );
	}

	/**
	 * Flag vendor scripts required by a listing config.
	 *
	 * @param array $config Listing JS config.
	 * @return void
	 */
	private function flag_listing_scripts_for_config( $config ) {
		if ( empty( $config ) ) {
			return;
		}

		if ( ! empty( $config['isotope'] ) ) {
			self::flag_listing_script( 'isotope' );
		}

		self::flag_listing_script( 'dinofolio' );
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
		
		// Container classes
		$container_classes = array(
			'dinofolio',
			'dinofolio-portfolio-listing',
			'dinofolio-layout-' . $attributes['layout'],
			'dinofolio-columns-' . $attributes['columns'],
			'dinofolio-style-' . $attributes['style'],
			'dinofolio-color-scheme-' . $attributes['colorScheme'],
		);

		$listing_config = $this->get_listing_js_config( $attributes );
		$config_attr    = '';

		if ( ! empty( $attributes['enableParallax'] ) ) {
			$container_classes[] = 'dinofolio-parallax-enabled';
		}

		// Isotope only runs on the front end; keep native CSS layouts in the block editor preview.
		if ( ! empty( $listing_config['isotope'] ) && ! self::is_block_editor_preview() ) {
			$container_classes[] = 'dinofolio-uses-isotope';
		}

		if ( ! empty( $attributes['className'] ) ) {
			$container_classes[] = $attributes['className'];
		}

		if ( ! empty( $attributes['showReadMore'] ) && 'standard' === $attributes['style'] ) {
			$container_classes[] = 'dinofolio-read-more-align-' . sanitize_html_class(
				$this->normalize_read_more_align( isset( $attributes['readMoreAlign'] ) ? $attributes['readMoreAlign'] : 'right' )
			);
		}

		if ( $attributes['lightbox'] ) {
			$container_classes[] = 'dinofolio-lightbox-enabled';
		}

		if ( self::is_block_editor_preview() ) {
			$container_classes[] = 'dinofolio-listing--editor-preview';
		}

		if ( $attributes['showFilter'] ) {
			$container_classes[] = 'dinofolio-has-category-filter';

			if ( ! empty( $attributes['showFilterCount'] ) ) {
				$container_classes[] = 'dinofolio-show-filter-count';
			}
		}

		/**
		 * Filter listing container CSS classes.
		 *
		 * @param array    $container_classes Container class names.
		 * @param array    $attributes        Merged listing attributes.
		 * @param WP_Query $query             Portfolio query.
		 */
		$container_classes = apply_filters( 'dinofolio_listing_container_classes', $container_classes, $attributes, $query );

		$listing_context = $this->prepare_listing_query_context( $query, $attributes );
		$gallery_attr    = $listing_context['gallery_attr'];

		if ( ! empty( $listing_config ) && ! self::is_block_editor_preview() ) {
			$this->flag_listing_scripts_for_config( $listing_config );
			$config_attr = ' data-dinofolio-config="' . esc_attr( wp_json_encode( $listing_config ) ) . '"';
		}

		// Start container
		$output .= '<div class="' . esc_attr( implode( ' ', $container_classes ) ) . '"' . $this->get_listing_inline_style_attr( $attributes ) . $gallery_attr . $config_attr . '>';

		// Add filter if enabled
		if ( $attributes['showFilter'] ) {
			$output .= $this->get_filter_html( $attributes, $query );
		}

		// Add portfolio items wrapper - match SCSS structure
		$output .= '<div class="dinofolio-items-list">';

		$output .= $this->get_portfolio_items_html( $query, $attributes );

		$output .= '</div>'; // Close portfolio grid

		$pagination_mode = isset( $attributes['paginationMode'] ) ? $attributes['paginationMode'] : 'none';

		// Add pagination or load more when enabled.
		if ( 'pagination' === $pagination_mode && $query->max_num_pages > 1 ) {
			$output .= $this->get_pagination_html( $query, $attributes );
		} elseif ( 'load_more' === $pagination_mode && $query->max_num_pages > 1 ) {
			$output .= $this->get_load_more_html( $query, $attributes );
		}

		// Add view all link if enabled.
		if ( $attributes['showViewAll'] ) {
			$output .= $this->get_view_all_html( $attributes );
		}

		$output .= '</div>'; // Close container

		// Reset post data
		wp_reset_postdata();

		/**
		 * Filter generated portfolio listing HTML.
		 *
		 * @param string   $output     Listing HTML.
		 * @param array    $attributes Merged listing attributes.
		 * @param WP_Query $query      Portfolio query.
		 */
		$output = apply_filters( 'dinofolio_listing_html', $output, $attributes, $query );

		/**
		 * Fires after a portfolio listing HTML is generated.
		 *
		 * @param array    $attributes Merged listing attributes.
		 * @param WP_Query $query      Portfolio query.
		 */
		do_action( 'dinofolio_after_render_listing', $attributes, $query );

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
		$classes = array( 'dinofolio-item' );
		
		// Add categories as classes for filtering
		$terms = get_the_terms( $post_id, $this->taxonomies[0] );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$classes[] = 'dinofolio-cat-' . $term->slug;
			}
		}

		if ( ! empty( $attributes['layout'] ) && 'masonry' === $attributes['layout'] ) {
			$classes[] = 'dinofolio-is-masonry-item';
		}

		$gallery_image_ids = $this->get_listing_gallery_image_ids( $post_id );
		if ( ! empty( $gallery_image_ids ) ) {
			$classes[] = 'dinofolio-item--has-gallery';
		}

		if ( ! empty( $attributes['style'] ) && 'overlay' === $attributes['style'] ) {
			if ( ! empty( $attributes['layout'] ) && 'masonry' === $attributes['layout'] ) {
				$classes[] = 'dinofolio-is-masonry-item';
			}

			return apply_filters( 'dinofolio_portfolio_item_html', $this->get_portfolio_overlay_item_html( $attributes, $post_id, $classes ), $attributes, $post_id );
		}

		// Default grid / masonry card structure
		$details_html = '';
		if ( $attributes['showCategories'] && $terms && ! is_wp_error( $terms ) ) {
			$details_html .= $this->get_portfolio_item_categories_html( $terms );
		}
		if ( $attributes['showTitle'] ) {
			$details_html .= '<h3 class="dinofolio-item-title">';
			$details_html .= '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
			$details_html .= '</h3>';
		}
		if ( $attributes['showExcerpt'] ) {
			$excerpt = $this->get_listing_excerpt_text( $post_id );
			$excerpt = $this->trim_listing_excerpt( $excerpt, $attributes );
			if ( $excerpt ) {
				$details_html .= $this->get_portfolio_item_excerpt_html( $excerpt );
			}
		}
		if ( $attributes['showReadMore'] ) {
			$read_more_label = ! empty( $attributes['readMoreLabel'] ) ? $attributes['readMoreLabel'] : esc_html__( 'View Project', 'dinofolio' );
			$details_html .= '<div class="dinofolio-item-button">';
			$details_html .= $this->get_read_more_link_html( get_permalink(), $read_more_label );
			$details_html .= '</div>';
		}

		$output = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$output .= $this->get_portfolio_item_image( $attributes, $post_id );
		if ( '' !== $details_html ) {
			$output .= '<div class="dinofolio-item-details">' . $details_html . '</div>';
		}
		$output .= '</div>';
		return apply_filters( 'dinofolio_portfolio_item_html', $output, $attributes, $post_id );
	}

	/**
	 * Render a single portfolio item markup for extension layouts.
	 *
	 * @param array $attributes Merged listing attributes.
	 * @param int   $post_id    Portfolio post ID.
	 * @return string
	 */
	public function render_portfolio_item_markup( $attributes, $post_id = 0 ) {
		$post_id = (int) $post_id;

		if ( $post_id <= 0 ) {
			$post_id = get_the_ID();
		}

		if ( $post_id <= 0 ) {
			return '';
		}

		$post = get_post( $post_id );

		if ( ! $post || 'wpdino_portfolio' !== $post->post_type ) {
			return '';
		}

		global $wp_query;

		$previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;

		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );

		$html = $this->get_portfolio_item_html( $attributes );

		if ( $previous_post instanceof \WP_Post ) {
			$GLOBALS['post'] = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $previous_post );
		} else {
			wp_reset_postdata();
		}

		return $html;
	}

	/**
	 * Build "View Project" link markup.
	 *
	 * @param string $url   Destination URL.
	 * @param string $label Link label.
	 * @return string
	 */
	private function get_read_more_link_html( $url, $label ) {
		$output  = '<a href="' . esc_url( $url ) . '" class="dinofolio-button-link">';
		$output .= '<span class="dinofolio-button-link-text">' . esc_html( $label ) . '</span>';
		$output .= '<span class="dinofolio-button-link-icon" aria-hidden="true">';
		$output .= $this->get_read_more_icon_svg();
		$output .= '</span>';
		$output .= '</a>';

		return $output;
	}

	/**
	 * Circled arrow icon for read-more links.
	 *
	 * @return string
	 */
	private function get_read_more_icon_svg() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9.25"/><path d="m10 8 4 4-4 4"/></svg>';
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

		$pills = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$icon_html = class_exists( '\DinoFolio\Portfolio_Category_Icon' )
				? \DinoFolio\Portfolio_Category_Icon::render_icon_html( $term )
				: '';

			$pills[] = '<a class="dinofolio-category-pill" href="' . esc_url( get_term_link( $term ) ) . '">'
				. $icon_html
				. '<span class="dinofolio-category-pill-label">' . esc_html( $term->name ) . '</span>'
				. '</a>';
		}

		if ( empty( $pills ) ) {
			return '';
		}

		return '<div class="dinofolio-item-categories">' . implode( '', $pills ) . '</div>';
	}

	/**
	 * Build excerpt markup for listing cards.
	 *
	 * @param string $excerpt Post excerpt text.
	 * @return string
	 */
	private function get_portfolio_item_excerpt_html( $excerpt ) {
		$excerpt = trim( (string) $excerpt );

		if ( '' === $excerpt ) {
			return '';
		}

		$excerpt_content = wp_kses_post( $excerpt );

		if ( '' === trim( wp_strip_all_tags( $excerpt_content ) ) ) {
			return '';
		}

		if ( false === strpos( $excerpt_content, '<p' ) ) {
			$excerpt_content = '<p>' . $excerpt_content . '</p>';
		}

		return '<div class="dinofolio-item-excerpt">' . $excerpt_content . '</div>';
	}

	/**
	 * Normalize excerpt character limit.
	 *
	 * @param mixed $length Raw excerpt length.
	 * @return int
	 */
	private function normalize_excerpt_length( $length ) {
		$length = (int) $length;

		if ( $length < 1 ) {
			$length = 120;
		}

		return max( 20, min( 1000, $length ) );
	}

	/**
	 * Get raw excerpt text for listing cards without WordPress' default word trim.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_listing_excerpt_text( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return '';
		}

		$excerpt = trim( (string) $post->post_excerpt );

		if ( '' !== $excerpt ) {
			return $excerpt;
		}

		$content = (string) $post->post_content;
		$content = strip_shortcodes( $content );
		$content = excerpt_remove_blocks( $content );
		$content = wp_strip_all_tags( $content );
		$content = str_replace( array( "\r\n", "\r", "\n" ), ' ', $content );
		$content = preg_replace( '/\s+/', ' ', $content );

		return trim( (string) $content );
	}

	/**
	 * Trim excerpt based on listing settings.
	 *
	 * @param string $excerpt    Raw excerpt text.
	 * @param array  $attributes Listing attributes.
	 * @return string
	 */
	private function trim_listing_excerpt( $excerpt, $attributes ) {
		$excerpt = trim( (string) $excerpt );

		if ( '' === $excerpt ) {
			return '';
		}

		$char_limit = $this->normalize_excerpt_length(
			isset( $attributes['excerptLength'] ) ? $attributes['excerptLength'] : 120
		);
		$excerpt    = wp_strip_all_tags( $excerpt );

		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $excerpt ) > $char_limit ) {
				return rtrim( mb_substr( $excerpt, 0, $char_limit ) ) . '…';
			}

			return $excerpt;
		}

		if ( strlen( $excerpt ) > $char_limit ) {
			return rtrim( substr( $excerpt, 0, $char_limit ) ) . '…';
		}

		return $excerpt;
	}

	/**
	 * Overlay layout item: image only by default; icons + caption on hover.
	 *
	 * @param array $attributes Merged listing attributes.
	 * @param int   $post_id    Post ID.
	 * @param array $classes    Item CSS classes.
	 * @return string
	 */
	private function get_portfolio_overlay_item_html( $attributes, $post_id, $classes ) {
		$gallery_image_ids = $this->get_listing_gallery_image_ids( $post_id );
		$thumbnail_id      = get_post_thumbnail_id( $post_id );

		if ( empty( $gallery_image_ids ) && ! $thumbnail_id ) {
			return '';
		}

		$image_classes = array( 'dinofolio-item-image' );

		if ( ! empty( $attributes['enableParallax'] ) ) {
			$image_classes[] = 'dinofolio-parallax-target';
		}

		$has_caption = ! empty( $attributes['showTitle'] ) || ! empty( $attributes['showExcerpt'] );

		$video_settings     = \DinoFolio\Portfolio_Video::get_item_settings( $post_id );
		$lightbox_video     = $video_settings['lightbox'];
		$use_video_lightbox = ! empty( $attributes['lightbox'] ) && ! empty( $lightbox_video['enabled'] );

		if ( ! empty( $attributes['lightbox'] ) ) {
			self::flag_lightbox_assets();
		}

		$classes[] = 'dinofolio-item--overlay';

		if ( ! empty( $gallery_image_ids ) ) {
			$classes[] = 'dinofolio-item--has-gallery';
		}

		$overlay_classes = array( 'dinofolio-item-thumbnail', 'dinofolio-overlay-card' );

		if ( ! empty( $gallery_image_ids ) ) {
			$overlay_classes[] = 'dinofolio-item-thumbnail--gallery';
		}

		$output  = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$output .= '<div class="' . esc_attr( implode( ' ', $overlay_classes ) ) . '">';

		if ( ! empty( $gallery_image_ids ) ) {
			$output .= $this->get_portfolio_item_gallery_carousel_inner_html( $attributes, $post_id, $gallery_image_ids, 'overlay' );
		} else {
			$output .= wp_get_attachment_image(
				$thumbnail_id,
				$attributes['imageSize'],
				false,
				array(
					'class' => implode( ' ', $image_classes ),
					'alt'   => esc_attr( get_the_title() ),
				)
			);
		}

		$output .= '<div class="dinofolio-overlay-panel">';

		$output .= '<div class="dinofolio-overlay-actions">';

		if ( $use_video_lightbox ) {
			$output .= '<a ' . $this->get_video_lightbox_link_attributes( $lightbox_video, get_the_title(), 'dinofolio-overlay-action dinofolio-overlay-action--video' ) . '>';
			$output .= $this->get_overlay_video_icon_svg();
			$output .= '<span class="screen-reader-text">' . esc_html__( 'Play video in lightbox', 'dinofolio' ) . '</span>';
			$output .= '</a>';
		} elseif ( ! empty( $attributes['lightbox'] ) ) {
			if ( ! empty( $gallery_image_ids ) ) {
				$first_full = wp_get_attachment_image_src( (int) $gallery_image_ids[0], 'full' );
				$full_url   = $first_full ? $first_full[0] : '';
			} else {
				$full_image = wp_get_attachment_image_src( $thumbnail_id, 'full' );
				$full_url   = $full_image ? $full_image[0] : '';
			}

			if ( $full_url ) {
				$gallery_group = ! empty( $gallery_image_ids )
					? 'dinofolio-item-gallery-' . $post_id
					: '';
				$output       .= '<a ' . $this->get_lightbox_link_attributes( $full_url, get_the_title(), 'dinofolio-overlay-action dinofolio-overlay-action--zoom', $gallery_group ) . '>';
				$output       .= $this->get_overlay_zoom_icon_svg();
				$output       .= '<span class="screen-reader-text">' . esc_html__( 'Open in lightbox', 'dinofolio' ) . '</span>';
				$output       .= '</a>';
			}
		}

		$output .= '<a href="' . esc_url( get_permalink() ) . '" class="dinofolio-overlay-action dinofolio-overlay-action--link" aria-label="' . esc_attr( get_the_title() ) . '">';
		$output .= $this->get_overlay_link_icon_svg();
		$output .= '<span class="screen-reader-text">' . esc_html__( 'View project', 'dinofolio' ) . '</span>';
		$output .= '</a>';

		$output .= '</div>';

		if ( $has_caption ) {
			$output .= '<div class="dinofolio-overlay-caption">';

			if ( ! empty( $attributes['showTitle'] ) ) {
				$output .= '<h3 class="dinofolio-item-title">' . esc_html( get_the_title() ) . '</h3>';
			}

			if ( ! empty( $attributes['showExcerpt'] ) ) {
				$excerpt = $this->get_listing_excerpt_text( $post_id );
				$excerpt = $this->trim_listing_excerpt( $excerpt, $attributes );
				if ( $excerpt ) {
					$output .= $this->get_portfolio_item_excerpt_html( $excerpt );
				}
			}

			$output .= '</div>';
		}

		$output .= '</div>'; // panel
		$output .= '</div>'; // thumbnail
		$output .= '</div>'; // item

		return $output;
	}

	/**
	 * Zoom icon for overlay layout action buttons.
	 *
	 * @return string
	 */
	private function get_overlay_zoom_icon_svg() {
		return '<svg class="dinofolio-overlay-icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>';
	}

	/**
	 * Link icon for overlay layout action buttons.
	 *
	 * @return string
	 */
	private function get_overlay_link_icon_svg() {
		return '<svg class="dinofolio-overlay-icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>';
	}

	/**
	 * Zoom icon overlay for lightbox thumbnails.
	 *
	 * @return string
	 */
	private function get_lightbox_zoom_icon_html() {
		if ( self::is_block_editor_preview() ) {
			return '';
		}

		$svg = '<svg class="dinofolio-zoom-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>';

		return '<span class="dinofolio-lightbox-zoom-icon" aria-hidden="true">' . $svg . '</span>';
	}

	/**
	 * Build a sanitized HTML attribute string.
	 *
	 * @param array $attributes Attribute names and values.
	 * @return string
	 */
	private function build_html_attributes( array $attributes ) {
		$html = '';

		foreach ( $attributes as $name => $value ) {
			if ( '' === $value && 'data-glightbox' !== $name ) {
				continue;
			}

			if ( 'data-glightbox' === $name && '' === $value ) {
				$html .= ' data-glightbox';
				continue;
			}

			if ( 'href' === $name ) {
				$html .= sprintf( ' href="%s"', esc_url( $value ) );
				continue;
			}

			$html .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
		}

		return trim( $html );
	}

	/**
	 * Build attributes for a GLightbox video link.
	 *
	 * @param array  $video         Video settings.
	 * @param string $title         Accessible label.
	 * @param string $extra_classes Optional CSS classes.
	 * @return string
	 */
	private function get_video_lightbox_link_attributes( $video, $title = '', $extra_classes = '' ) {
		$gallery_id = self::$listing_gallery_id ? self::$listing_gallery_id : 'dinofolio-gallery';
		$class      = 'glightbox dinofolio-lightbox-link dinofolio-video-lightbox-link';

		if ( $extra_classes ) {
			$class .= ' ' . $extra_classes;
		}

		$atts = array(
			'href'         => $video['url'],
			'class'        => $class,
			'data-gallery' => $gallery_id,
			'data-type'    => 'video',
			'aria-label'   => $title ? $title : __( 'Play video in lightbox', 'dinofolio' ),
		);

		if ( $title ) {
			$atts['data-title'] = $title;
		}

		return $this->build_html_attributes( $atts );
	}

	/**
	 * Play icon overlay for video lightbox thumbnails.
	 *
	 * @return string
	 */
	private function get_lightbox_video_icon_html() {
		if ( self::is_block_editor_preview() ) {
			return '';
		}

		$svg = '<svg class="dinofolio-play-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><polygon points="8 5 19 12 8 19 8 5"></polygon></svg>';

		return '<span class="dinofolio-lightbox-video-icon" aria-hidden="true">' . $svg . '</span>';
	}

	/**
	 * Play icon for overlay layout video action.
	 *
	 * @return string
	 */
	private function get_overlay_video_icon_svg() {
		return '<svg class="dinofolio-overlay-icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><polygon points="8 5 19 12 8 19 8 5"></polygon></svg>';
	}

	private function get_lightbox_link_attributes( $image_url, $title = '', $extra_classes = '', $gallery_id = '' ) {
		if ( '' === $gallery_id ) {
			$gallery_id = self::$listing_gallery_id ? self::$listing_gallery_id : 'dinofolio-gallery';
		}

		$class = 'glightbox dinofolio-lightbox-link';

		if ( $extra_classes ) {
			$class .= ' ' . $extra_classes;
		}

		$atts = array(
			'href'           => $image_url,
			'class'          => $class,
			'data-glightbox' => '',
			'data-gallery'   => $gallery_id,
			'data-type'      => 'image',
			'aria-label'     => $title ? $title : __( 'View image in lightbox', 'dinofolio' ),
		);

		if ( $title ) {
			$atts['data-title'] = $title;
		}

		return $this->build_html_attributes( $atts );
	}

	/**
	 * Ensure gallery carousel JS loads when a listing includes gallery-format items.
	 *
	 * @param WP_Query $query Portfolio query.
	 * @return void
	 */
	private function flag_gallery_carousel_assets_for_query( $query ) {
		if ( ! $query instanceof WP_Query || empty( $query->posts ) || ! class_exists( '\DinoFolio\Util' ) ) {
			return;
		}

		foreach ( $query->posts as $post ) {
			$post_id = isset( $post->ID ) ? (int) $post->ID : 0;

			if ( $post_id < 1 || ! \DinoFolio\Util::is_portfolio_gallery_format( $post_id ) ) {
				continue;
			}

			if ( ! empty( \DinoFolio\Util::get_portfolio_gallery_image_ids( $post_id ) ) ) {
				self::flag_listing_script( 'dinofolio' );
				return;
			}
		}
	}

	/**
	 * Prepare listing assets and container attributes for extension layouts.
	 *
	 * @param WP_Query $query      Portfolio query.
	 * @param array    $attributes Merged listing attributes.
	 * @return array{gallery_attr: string}
	 */
	public function prepare_listing_query_context( $query, $attributes ) {
		$gallery_attr = '';

		if ( ! empty( $attributes['lightbox'] ) ) {
			self::flag_lightbox_assets();
			self::$listing_gallery_id = 'dinofolio-gallery-' . wp_unique_id();

			if ( ! self::is_block_editor_preview() ) {
				$this->enqueue_lightbox_assets();
			}

			$gallery_attr = ' data-dinofolio-gallery="' . esc_attr( self::$listing_gallery_id ) . '"';
		} else {
			self::$listing_gallery_id = '';
		}

		$this->flag_gallery_carousel_assets_for_query( $query );

		return array(
			'gallery_attr' => $gallery_attr,
		);
	}

	/**
	 * Gallery image IDs for listing cards when the post uses gallery format.
	 *
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	private function get_listing_gallery_image_ids( $post_id ) {
		if ( ! class_exists( '\DinoFolio\Util' ) || ! \DinoFolio\Util::is_portfolio_gallery_format( $post_id ) ) {
			return array();
		}

		return \DinoFolio\Util::get_portfolio_gallery_image_ids( $post_id );
	}

	/**
	 * Resolve listing image size for gallery carousel slides.
	 *
	 * @param array $attributes Listing attributes.
	 * @return string
	 */
	private function get_listing_gallery_image_size( $attributes ) {
		return 'dinofolio-featured-1200x900';
	}

	/**
	 * Build listing gallery carousel markup (inner carousel only).
	 *
	 * @param array  $attributes Listing attributes.
	 * @param int    $post_id    Post ID.
	 * @param int[]  $image_ids  Gallery attachment IDs.
	 * @param string $context    Either "standard" or "overlay".
	 * @return string
	 */
	private function get_portfolio_item_gallery_carousel_inner_html( $attributes, $post_id, $image_ids, $context = 'standard' ) {
		$image_size      = $this->get_listing_gallery_image_size( $attributes );
		$use_lightbox    = ! empty( $attributes['lightbox'] );
		$gallery_group   = 'dinofolio-item-gallery-' . (int) $post_id;
		$image_classes   = array( 'dinofolio-item-image' );
		$title           = get_the_title( $post_id );
		$is_overlay      = ( 'overlay' === $context );

		if ( ! empty( $attributes['enableParallax'] ) ) {
			$image_classes[] = 'dinofolio-parallax-target';
		}

		if ( $use_lightbox ) {
			self::flag_lightbox_assets();
		}

		$output  = '<div class="dinofolio-item-gallery-carousel" data-dinofolio-item-gallery-carousel>';
		$output .= '<button type="button" class="dinofolio-carousel-nav dinofolio-carousel-prev" aria-label="' . esc_attr__( 'Previous image', 'dinofolio' ) . '">';
		$output .= $this->get_carousel_nav_icon_svg( 'prev' );
		$output .= '</button>';
		$output .= '<div class="dinofolio-item-gallery-carousel-viewport">';
		$output .= '<div class="dinofolio-item-gallery-carousel-track">';

		foreach ( $image_ids as $image_id ) {
			$image_id   = (int) $image_id;
			$wrap_link  = false;
			$output    .= '<figure class="dinofolio-item-gallery-slide">';

			if ( $use_lightbox ) {
				$full_image = wp_get_attachment_image_src( $image_id, 'full' );
				$full_url   = $full_image ? $full_image[0] : '';

				if ( $full_url ) {
					$output   .= '<a ' . $this->get_lightbox_link_attributes( $full_url, $title, '', $gallery_group ) . '>';
					$wrap_link = true;
				}
			} elseif ( ! $is_overlay ) {
				$output   .= '<a href="' . esc_url( get_permalink( $post_id ) ) . '">';
				$wrap_link = true;
			}

			$output .= wp_get_attachment_image(
				$image_id,
				$image_size,
				false,
				array(
					'class' => implode( ' ', $image_classes ),
					'alt'   => esc_attr( $title ),
					'sizes' => '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw',
				)
			);

			if ( $wrap_link ) {
				$output .= '</a>';
			}

			$output .= '</figure>';
		}

		$output .= '</div>';
		$output .= '</div>';
		$output .= '<button type="button" class="dinofolio-carousel-nav dinofolio-carousel-next" aria-label="' . esc_attr__( 'Next image', 'dinofolio' ) . '">';
		$output .= $this->get_carousel_nav_icon_svg( 'next' );
		$output .= '</button>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Build listing gallery carousel thumbnail wrapper.
	 *
	 * @param array $attributes Listing attributes.
	 * @param int   $post_id    Post ID.
	 * @param int[] $image_ids  Gallery attachment IDs.
	 * @return string
	 */
	private function get_portfolio_item_gallery_carousel_html( $attributes, $post_id, $image_ids ) {
		$classes = array( 'dinofolio-item-thumbnail', 'dinofolio-item-thumbnail--gallery' );

		if ( ! empty( $attributes['lightbox'] ) ) {
			$classes[] = 'dinofolio-lightbox';
		}

		$output  = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$output .= $this->get_portfolio_item_gallery_carousel_inner_html( $attributes, $post_id, $image_ids, 'standard' );
		$output .= '</div>';

		return $output;
	}

	/**
	 * Carousel navigation icon SVG.
	 *
	 * @param string $direction Either "prev" or "next".
	 * @return string
	 */
	private function get_carousel_nav_icon_svg( $direction = 'prev' ) {
		if ( 'next' === $direction ) {
			return '<svg class="dinofolio-carousel-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		}

		return '<svg class="dinofolio-carousel-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	/**
	 * Get portfolio item image HTML
	 *
	 * @param array $attributes The merged attributes
	 * @param int $post_id Post ID
	 * @return string Image HTML
	 */
	private function get_portfolio_item_image( $attributes, $post_id ) {
		$gallery_image_ids = $this->get_listing_gallery_image_ids( $post_id );

		if ( ! empty( $gallery_image_ids ) ) {
			return $this->get_portfolio_item_gallery_carousel_html( $attributes, $post_id, $gallery_image_ids );
		}

		$image_size = $attributes['imageSize'];
		if ( ! empty( $attributes['layout'] ) && 'masonry' === $attributes['layout'] ) {
			$image_size = 'large';
		}
		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( ! $thumbnail_id ) {
			return '';
		}

		$video_settings     = \DinoFolio\Portfolio_Video::get_item_settings( $post_id );
		$lightbox_video     = $video_settings['lightbox'];
		$use_video_lightbox = ! empty( $attributes['lightbox'] ) && ! empty( $lightbox_video['enabled'] );

		$classes = array( 'dinofolio-item-thumbnail' );

		if ( $attributes['lightbox'] ) {
			$classes[] = 'dinofolio-lightbox';
		}

		$image_classes = array( 'dinofolio-item-image' );

		if ( ! empty( $attributes['enableParallax'] ) ) {
			$image_classes[] = 'dinofolio-parallax-target';
		}

		$image_attrs = array(
			'class' => implode( ' ', $image_classes ),
			'alt'   => esc_attr( get_the_title() ),
		);

		$output  = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		if ( $use_video_lightbox ) {
			self::flag_lightbox_assets();
			$output .= '<a ' . $this->get_video_lightbox_link_attributes( $lightbox_video, get_the_title() ) . '>';
			$output .= wp_get_attachment_image( $thumbnail_id, $image_size, false, $image_attrs );
			$output .= $this->get_lightbox_video_icon_html();
			$output .= '</a>';
		} elseif ( $attributes['lightbox'] ) {
			$full_image = wp_get_attachment_image_src( $thumbnail_id, 'full' );
			$output    .= '<a ' . $this->get_lightbox_link_attributes( $full_image[0], get_the_title() ) . '>';
			$output    .= wp_get_attachment_image( $thumbnail_id, $image_size, false, $image_attrs );
			$output    .= $this->get_lightbox_zoom_icon_html();
			$output    .= '</a>';
		} else {
			$output .= '<a href="' . esc_url( get_permalink() ) . '">';
			$output .= wp_get_attachment_image( $thumbnail_id, $image_size, false, $image_attrs );
			$output .= '</a>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Collect category terms used by posts in a listing query.
	 *
	 * @param WP_Query $query Portfolio query.
	 * @return array<int, WP_Term>
	 */
	private function get_filter_terms_for_query( $query ) {
		$terms_by_id = array();

		if ( ! $query instanceof WP_Query || empty( $query->posts ) ) {
			return array();
		}

		foreach ( $query->posts as $post ) {
			$post_terms = get_the_terms( $post->ID, $this->taxonomies[0] );

			if ( ! $post_terms || is_wp_error( $post_terms ) ) {
				continue;
			}

			foreach ( $post_terms as $term ) {
				$terms_by_id[ $term->term_id ] = $term;
			}
		}

		$terms = array_values( $terms_by_id );

		usort(
			$terms,
			static function ( $a, $b ) {
				return strcasecmp( $a->name, $b->name );
			}
		);

		return $terms;
	}

	/**
	 * Count portfolio items per category slug in a listing query.
	 *
	 * @param WP_Query $query Portfolio query.
	 * @return array{__all__: int, string: int}
	 */
	private function get_category_counts_for_query( $query ) {
		$counts = array(
			'__all__' => 0,
		);

		if ( ! $query instanceof WP_Query || empty( $query->posts ) ) {
			return $counts;
		}

		$counts['__all__'] = count( $query->posts );

		foreach ( $query->posts as $post ) {
			$post_terms = get_the_terms( $post->ID, $this->taxonomies[0] );

			if ( ! $post_terms || is_wp_error( $post_terms ) ) {
				continue;
			}

			foreach ( $post_terms as $term ) {
				if ( ! isset( $counts[ $term->slug ] ) ) {
					$counts[ $term->slug ] = 0;
				}

				++$counts[ $term->slug ];
			}
		}

		return $counts;
	}

	/**
	 * Build a single category filter link.
	 *
	 * @param string   $label       Link label.
	 * @param string   $filter      data-filter value (* or .dinofolio-cat-slug).
	 * @param int|null $count       Item count for this tab.
	 * @param bool     $show_count  Whether to output the count badge.
	 * @return string
	 */
	private function get_filter_link_html( $label, $filter, $count, $show_count ) {
		$output  = '<a href="#" class="dinofolio-filter-link" data-filter="' . esc_attr( $filter ) . '">';
		$output .= '<span class="dinofolio-filter-label">' . esc_html( $label ) . '</span>';

		if ( $show_count && null !== $count ) {
			/* translators: %d: number of portfolio items in this category */
			$count_label = sprintf( _n( '%d item', '%d items', (int) $count, 'dinofolio' ), (int) $count );
			$output     .= '<span class="dinofolio-filter-count" aria-label="' . esc_attr( $count_label ) . '">' . esc_html( (string) (int) $count ) . '</span>';
		}

		$output .= '</a>';

		return $output;
	}

	/**
	 * Get filter HTML
	 *
	 * @param array    $attributes The merged attributes.
	 * @param WP_Query $query      Portfolio query (limits tabs to categories in this listing).
	 * @return string Filter HTML
	 */
	private function get_filter_html( $attributes, $query = null ) {
		$terms = $this->get_filter_terms_for_query( $query );

		if ( empty( $terms ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $this->taxonomies[0],
					'hide_empty' => true,
				)
			);
		}

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$show_count = ! empty( $attributes['showFilterCount'] );
		$counts     = $show_count ? $this->get_category_counts_for_query( $query ) : array();

		$filter_classes = array( 'dinofolio-filter' );

		if ( $show_count ) {
			$filter_classes[] = 'dinofolio-show-filter-count';
		}

		$output  = '<div class="dinofolio-filter-categories">';
		$output .= '<nav class="' . esc_attr( implode( ' ', $filter_classes ) ) . '" aria-label="' . esc_attr__( 'Filter portfolio by category', 'dinofolio' ) . '">';
		$output .= '<ul role="list">';

		$all_count = isset( $counts['__all__'] ) ? (int) $counts['__all__'] : null;

		$output .= '<li class="dinofolio-current-cat" role="listitem">';
		$output .= $this->get_filter_link_html( __( 'All', 'dinofolio' ), '*', $all_count, $show_count );
		$output .= '</li>';

		foreach ( $terms as $term ) {
			$term_count = isset( $counts[ $term->slug ] ) ? (int) $counts[ $term->slug ] : 0;

			$output .= '<li role="listitem">';
			$output .= $this->get_filter_link_html(
				$term->name,
				'.dinofolio-cat-' . $term->slug,
				$term_count,
				$show_count
			);
			$output .= '</li>';
		}

		$output .= '</ul>';
		$output .= '</nav>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Normalize WordPress paginate_links() markup to dinofolio-prefixed classes.
	 *
	 * @param string $html Pagination HTML from paginate_links().
	 * @return string
	 */
	private function normalize_pagination_html( $html ) {
		if ( '' === $html ) {
			return '';
		}

		return (string) preg_replace_callback(
			'/class="([^"]*)"/',
			static function ( $matches ) {
				$classes = $matches[1];

				$classes = preg_replace( '/\bpage-numbers\s+current\b/', 'dinofolio-page-numbers dinofolio-current', $classes );
				$classes = preg_replace( '/\bpage-numbers\s+dots\b/', 'dinofolio-page-numbers dinofolio-dots', $classes );
				$classes = preg_replace( '/\bpage-numbers\b/', 'dinofolio-page-numbers', $classes );
				$classes = preg_replace( '/\bprev\b/', 'dinofolio-prev', $classes );
				$classes = preg_replace( '/\bnext\b/', 'dinofolio-next', $classes );

				return 'class="' . trim( preg_replace( '/\s+/', ' ', $classes ) ) . '"';
			},
			$html
		);
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

		$current_page = $this->get_listing_current_page();

		$output = '<div class="dinofolio-pagination">';

		// Build pagination for different contexts
		if ( is_home() || is_archive() || is_post_type_archive( $this->post_type ) ) {
			$big = 999999999;

			// For archive pages, use standard WordPress pagination
			$pagination_args = array(
				'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
				'format'    => '',
				'total'     => $total_pages,
				'current'   => $current_page,
				'prev_text' => '<span class="dinofolio-pagination-prev">&laquo; ' . esc_html__( 'Previous', 'dinofolio' ) . '</span>',
				'next_text' => '<span class="dinofolio-pagination-next">' . esc_html__( 'Next', 'dinofolio' ) . ' &raquo;</span>',
				'mid_size'  => 2,
				'end_size'  => 1,
				'before_page_number' => '<span class="dinofolio-page-number">',
				'after_page_number'  => '</span>',
			);
		} else {
			// For shortcodes, build pagination manually to avoid URL issues
			$output .= '<nav class="dinofolio-pagination-wrapper" aria-label="' . esc_attr__( 'Portfolio pagination', 'dinofolio' ) . '">';
			$output .= '<div class="dinofolio-pagination-items">';
			
			// Previous link
			if ( $current_page > 1 ) {
				$prev_url = add_query_arg( 'pg', $current_page - 1 );
				$output .= '<a class="dinofolio-page-numbers" href="' . esc_url( $prev_url ) . '">';
				$output .= '<span class="dinofolio-pagination-prev">&laquo; ' . esc_html__( 'Previous', 'dinofolio' ) . '</span>';
				$output .= '</a>';
			}
			
			// Page numbers
			$start_page = max( 1, $current_page - 2 );
			$end_page = min( $total_pages, $current_page + 2 );
			
			// First page if not in range
			if ( $start_page > 1 ) {
				$page_url = ( $total_pages == 1 ) ? remove_query_arg( 'pg' ) : add_query_arg( 'pg', 1 );
				$output .= '<a class="dinofolio-page-numbers" href="' . esc_url( $page_url ) . '">';
				$output .= '<span class="dinofolio-page-number">1</span>';
				$output .= '</a>';
				
				if ( $start_page > 2 ) {
					$output .= '<span class="dinofolio-page-numbers dinofolio-dots">…</span>';
				}
			}
			
			// Page range
			for ( $i = $start_page; $i <= $end_page; $i++ ) {
				if ( $i == $current_page ) {
					$output .= '<span class="dinofolio-page-numbers dinofolio-current">';
					$output .= '<span class="dinofolio-page-number">' . esc_html( (string) $i ) . '</span>';
					$output .= '</span>';
				} else {
					$page_url = ( $i == 1 ) ? remove_query_arg( 'pg' ) : add_query_arg( 'pg', $i );
					$output .= '<a class="dinofolio-page-numbers" href="' . esc_url( $page_url ) . '">';
					$output .= '<span class="dinofolio-page-number">' . esc_html( (string) $i ) . '</span>';
					$output .= '</a>';
				}
			}
			
			// Last page if not in range
			if ( $end_page < $total_pages ) {
				if ( $end_page < $total_pages - 1 ) {
					$output .= '<span class="dinofolio-page-numbers dinofolio-dots">…</span>';
				}
				
				$last_url = add_query_arg( 'pg', $total_pages );
				$output .= '<a class="dinofolio-page-numbers" href="' . esc_url( $last_url ) . '">';
				$output .= '<span class="dinofolio-page-number">' . esc_html( (string) $total_pages ) . '</span>';
				$output .= '</a>';
			}
			
			// Next link
			if ( $current_page < $total_pages ) {
				$next_url = add_query_arg( 'pg', $current_page + 1 );
				$output .= '<a class="dinofolio-page-numbers" href="' . esc_url( $next_url ) . '">';
				$output .= '<span class="dinofolio-pagination-next">' . esc_html__( 'Next', 'dinofolio' ) . ' &raquo;</span>';
				$output .= '</a>';
			}
			
			$output .= '</div>';
			$output .= '</nav>';
			$output .= '</div>';
			
			return $output;
		}

		$pagination_links = paginate_links( $pagination_args );

		if ( $pagination_links ) {
			$output .= '<nav class="dinofolio-pagination-wrapper" aria-label="' . esc_attr__( 'Portfolio pagination', 'dinofolio' ) . '">';
			$output .= $this->normalize_pagination_html( $pagination_links );
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
		$url = ! empty( $attributes['viewAllLink'] )
			? $attributes['viewAllLink']
			: get_post_type_archive_link( $this->post_type );

		if ( empty( $url ) ) {
			return '';
		}

		$label = ! empty( $attributes['viewAllText'] )
			? $attributes['viewAllText']
			: esc_html__( 'View All', 'dinofolio' );

		$output = '<div class="dinofolio-view-all">';
		$output .= '<a href="' . esc_url( $url ) . '" class="dinofolio-view-all-btn dinofolio-button-link">';
		$output .= esc_html( $label );
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
		
		return '<div class="dinofolio-no-posts">' . $message . '</div>';
	}

	/**
	 * Normalize pagination mode value.
	 *
	 * @param mixed $mode Raw pagination mode.
	 * @return string
	 */
	private function normalize_pagination_mode( $mode ) {
		$raw_mode = (string) $mode;

		if ( class_exists( '\DinoFolio\Util' ) ) {
			$resolved = \DinoFolio\Util::resolve_dropdown_value(
				$raw_mode,
				array(
					'none'       => esc_html__( 'None', 'dinofolio' ),
					'pagination' => esc_html__( 'Pagination', 'dinofolio' ),
					'load_more'  => esc_html__( 'Load More (AJAX)', 'dinofolio' ),
				)
			);

			if ( in_array( $resolved, array( 'none', 'pagination', 'load_more' ), true ) ) {
				return $resolved;
			}
		}

		$mode = sanitize_key( $raw_mode );

		if ( in_array( $mode, array( 'none', 'pagination', 'load_more' ), true ) ) {
			return $mode;
		}

		return 'pagination';
	}

	/**
	 * Normalize load more trigger value.
	 *
	 * @param mixed $trigger Raw load more trigger.
	 * @return string
	 */
	private function normalize_load_more_trigger( $trigger ) {
		$trigger = sanitize_key( (string) $trigger );

		if ( in_array( $trigger, array( 'click', 'in_view' ), true ) ) {
			return $trigger;
		}

		return 'click';
	}

	/**
	 * Normalize read more button alignment.
	 *
	 * @param mixed $align Raw alignment value.
	 * @return string
	 */
	private function normalize_read_more_align( $align ) {
		$align = sanitize_key( (string) $align );

		if ( in_array( $align, array( 'left', 'center', 'right' ), true ) ) {
			return $align;
		}

		return 'right';
	}

	/**
	 * Build HTML for all items in a portfolio query.
	 *
	 * @param WP_Query $query      Portfolio query.
	 * @param array    $attributes Listing attributes.
	 * @return string
	 */
	private function get_portfolio_items_html( $query, $attributes ) {
		$output = '';

		while ( $query->have_posts() ) {
			$query->the_post();
			$output .= $this->get_portfolio_item_html( $attributes );
		}

		return $output;
	}

	/**
	 * Attributes required to rebuild a listing for AJAX load more.
	 *
	 * @param array $attributes Merged listing attributes.
	 * @return array
	 */
	private function get_load_more_query_payload( $attributes ) {
		$keys = array(
			'layout',
			'columns',
			'postsToShow',
			'showTitle',
			'showCategories',
			'showExcerpt',
			'excerptLength',
			'showReadMore',
			'readMoreLabel',
			'imageSize',
			'lightbox',
			'orderBy',
			'order',
			'categories',
			'tags',
			'style',
			'colorScheme',
			'hoverEffect',
			'enableParallax',
			'gap',
			'radius',
			'accentColor',
			'hoverColor',
			'buttonTextColor',
			'mutedColor',
			'className',
			'showFilter',
			'showFilterCount',
		);

		$payload = array();

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $attributes ) ) {
				$payload[ $key ] = $attributes[ $key ];
			}
		}

		/**
		 * Filter AJAX load-more query payload attributes.
		 *
		 * @param array $payload    Attributes sent to the browser.
		 * @param array $attributes Merged listing attributes.
		 */
		return apply_filters( 'dinofolio_load_more_query_payload', $payload, $attributes );
	}

	/**
	 * Load more button markup for AJAX pagination.
	 *
	 * @param WP_Query $query      Portfolio query.
	 * @param array    $attributes Listing attributes.
	 * @return string
	 */
	private function get_load_more_html( $query, $attributes ) {
		$total_pages  = (int) $query->max_num_pages;
		$current_page = max( 1, (int) $query->get( 'paged' ) );

		if ( $total_pages <= 1 || $current_page >= $total_pages ) {
			return '';
		}

		$label = ! empty( $attributes['loadMoreLabel'] )
			? $attributes['loadMoreLabel']
			: esc_html__( 'Load More', 'dinofolio' );
		$trigger = $this->normalize_load_more_trigger(
			isset( $attributes['loadMoreTrigger'] ) ? $attributes['loadMoreTrigger'] : 'click'
		);
		$wrap_classes = 'dinofolio-load-more';

		if ( 'in_view' === $trigger ) {
			$wrap_classes .= ' dinofolio-load-more--in-view';
		}

		$output  = '<div class="' . esc_attr( $wrap_classes ) . '" data-max-pages="' . esc_attr( $total_pages ) . '" data-current-page="' . esc_attr( $current_page ) . '" data-load-more-trigger="' . esc_attr( $trigger ) . '">';

		if ( 'click' === $trigger ) {
			$output .= '<button type="button" class="dinofolio-load-more-btn" data-page="' . esc_attr( $current_page ) . '" aria-busy="false">';
			$output .= '<span class="dinofolio-load-more-btn-text">' . esc_html( $label ) . '</span>';
			$output .= '</button>';
		}
		$output .= '<div class="dinofolio-load-more-preloader" hidden aria-hidden="true">';
		$output .= '<span class="dinofolio-load-more-preloader-dots" aria-hidden="true">';
		$output .= '<span class="dinofolio-load-more-preloader-dot"></span>';
		$output .= '<span class="dinofolio-load-more-preloader-dot"></span>';
		$output .= '<span class="dinofolio-load-more-preloader-dot"></span>';
		$output .= '</span>';
		$output .= '<span class="dinofolio-load-more-preloader-text">' . esc_html__( 'Loading', 'dinofolio' ) . '</span>';
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * AJAX handler for portfolio load more requests.
	 *
	 * @return void
	 */
	public function ajax_load_more() {
		check_ajax_referer( 'dinofolio_load_more', 'nonce' );

		$page = isset( $_POST['page'] ) ? max( 1, absint( wp_unslash( $_POST['page'] ) ) ) : 1;

		$attributes = array();
		if ( isset( $_POST['attributes'] ) && is_string( $_POST['attributes'] ) ) {
			$attributes_json = sanitize_textarea_field( wp_unslash( $_POST['attributes'] ) );
			$decoded         = json_decode( $attributes_json, true );
			if ( is_array( $decoded ) ) {
				$attributes = $decoded;
			}
		}

		if ( isset( $_POST['galleryId'] ) ) {
			self::$listing_gallery_id = sanitize_key( wp_unslash( (string) $_POST['galleryId'] ) );
		}

		$attributes['paged'] = $page;
		$attributes          = $this->merge_attributes_with_defaults( $attributes );

		$query = new WP_Query( $this->build_query( $attributes ) );

		if ( ! $query->have_posts() ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'No more portfolio items found.', 'dinofolio' ),
				),
				404
			);
		}

		$html = $this->get_portfolio_items_html( $query, $attributes );
		wp_reset_postdata();

		$response = array(
			'html'     => $html,
			'page'     => $page,
			'maxPages' => (int) $query->max_num_pages,
			'hasMore'  => $page < (int) $query->max_num_pages,
		);

		if ( ! empty( $attributes['showFilter'] ) ) {
			$filter_terms = array();

			foreach ( $this->get_filter_terms_for_query( $query ) as $term ) {
				$filter_terms[] = array(
					'slug'   => $term->slug,
					'name'   => $term->name,
					'filter' => '.dinofolio-cat-' . $term->slug,
				);
			}

			$response['filterTerms'] = $filter_terms;
		}

		/**
		 * Filter AJAX load-more response payload.
		 *
		 * @param array    $response   Response data.
		 * @param array    $attributes Merged listing attributes.
		 * @param WP_Query $query      Portfolio query.
		 */
		$response = apply_filters( 'dinofolio_load_more_response', $response, $attributes, $query );

		wp_send_json_success( $response );
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
			'excerpt_length' => 120,
			'show_read_more' => 'true', 
			'image_size'     => 'large',
			'order_by'       => 'date',
			'order'          => 'desc',
			'categories'     => '',
			'show_filter'       => 'false',
			'show_filter_count' => 'false',
			'show_view_all'  => 'false',
			'view_all_text'  => 'View All',
			'view_all_link'  => '',
			'class_name'      => '',
			'pagination_mode' => 'pagination',
			'pagination_type' => 'pagination',
			'load_more_label' => esc_html__( 'Load More', 'dinofolio' ),
			'load_more_trigger' => 'click',
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
			'excerptLength'  => $this->normalize_excerpt_length( $attributes['excerpt_length'] ),
			'showReadMore'   => filter_var( $attributes['show_read_more'], FILTER_VALIDATE_BOOLEAN ),
			'imageSize'      => sanitize_text_field( $attributes['image_size'] ),
			'orderBy'        => sanitize_text_field( $attributes['order_by'] ),
			'order'          => sanitize_text_field( $attributes['order'] ),
			'showFilter'      => filter_var( $attributes['show_filter'], FILTER_VALIDATE_BOOLEAN ),
			'showFilterCount' => filter_var( $attributes['show_filter_count'], FILTER_VALIDATE_BOOLEAN ),
			'showViewAll'    => filter_var( $attributes['show_view_all'], FILTER_VALIDATE_BOOLEAN ),
			'viewAllText'    => sanitize_text_field( $attributes['view_all_text'] ),
			'viewAllLink'    => esc_url_raw( $attributes['view_all_link'] ),
			'className'      => sanitize_text_field( $attributes['class_name'] ),
			'paginationMode' => $this->normalize_pagination_mode(
				! empty( $attributes['pagination_mode'] )
					? $attributes['pagination_mode']
					: ( ! empty( $attributes['pagination_type'] ) ? $attributes['pagination_type'] : ( filter_var( $attributes['show_pagination'], FILTER_VALIDATE_BOOLEAN ) ? 'pagination' : 'none' ) )
			),
			'loadMoreLabel'  => ! empty( $attributes['load_more_label'] ) ? sanitize_text_field( $attributes['load_more_label'] ) : esc_html__( 'Load More', 'dinofolio' ),
			'loadMoreTrigger' => $this->normalize_load_more_trigger(
				! empty( $attributes['load_more_trigger'] ) ? $attributes['load_more_trigger'] : 'click'
			),
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