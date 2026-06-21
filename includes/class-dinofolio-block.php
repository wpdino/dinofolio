<?php

/**
 * Custom DinoFolio Block
 *
 * @package WPDINO_Portfolio
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for handling Gutenberg blocks
 */
class WPDINO_Portfolio_Block {

	/**
	 * @var WPDINO_Portfolio_Block The reference to *Singleton* instance of this class
	 *
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * @var WPDINO_Portfolio_Block
	 */
	protected $display;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return WPDINO_Portfolio_Block The *Singleton* instance.
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

		$this->display = WPDINO_Portfolio_Display::get_instance();

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_filter( 'block_categories_all', array( $this, 'block_categories' ), 10, 2 );

	}

	/**
	 * Initialize the block.
	 */
	public function init() {
		
		// Register block assets (separated for editor vs frontend)
		$this->register_block_assets();

		// Register the portfolio block with optimized asset loading
		register_block_type(
			'wpdino-blocks/portfolio',
			array(
				'api_version'     => 2,
				'category'        => 'wpdino-blocks',
				'editor_script'   => 'wpdino-portfolio-dinofolio-editor',  // Only loads in editor
				'editor_style'    => 'wpdino-portfolio-dinofolio-editor-style', // Only loads in editor  
				'style'           => 'wpdino-portfolio-dinofolio-style',   // Only loads on frontend when block is present
				'script'          => 'wpdino-portfolio-dinofolio-frontend', // Only loads on frontend when block is present
				'render_callback' => array( $this, 'render' ),
				'attributes'      => $this->get_block_attributes(),
			)
		);
	}

	/**
	 * Add the WPDINO blocks category if needed
	 */
	public function block_categories( $categories ) {
		if ( empty( $categories ) || ( ! empty( $categories ) && is_array( $categories ) && ! in_array( 'wpdino-blocks', wp_list_pluck( $categories, 'slug' ) ) ) ) {
			$categories = array_merge(
				$categories,
				array(
					array(
						'slug'  => 'wpdino-blocks',
						'title' => esc_html__( 'WPDINO', 'dinofolio' ),
					),
				)
			);
		}

		return $categories;
	}

	/**
	 * Enqueue block editor assets - EDITOR ONLY
	 */
	public function enqueue_block_editor_assets() {
		
		// Load the built assets
		$asset_file = include( DINOFOLIO_PATH . 'build/assets.php' );
		
		// Enqueue the main block category script (editor only)
		wp_enqueue_script(
			'wpdino-portfolio-blocks',
			DINOFOLIO_URL . 'build/index.js',
			$asset_file['index.js']['dependencies'],
			$asset_file['index.js']['version'],
			true
		);
		
		// Enqueue the dinofolio block script (editor only)
		wp_enqueue_script(
			'wpdino-portfolio-dinofolio-editor',
			DINOFOLIO_URL . 'build/dinofolio.js',
			$asset_file['dinofolio.js']['dependencies'],
			$asset_file['dinofolio.js']['version'],
			true
		);
		
		// Pass server data to JavaScript (editor only)
		wp_localize_script(
			'wpdino-portfolio-dinofolio-editor',
			'wpdinoPortfolioBlock',
			array(
				'apiUrl'    => rest_url( 'wpdino-blocks/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'pluginUrl' => DINOFOLIO_URL,
				'portfolioSettings'  => WPDINO_Portfolio_Settings::get_all_settings(),
			)
		);
	}

	/**
	 * Register block assets with proper separation
	 */
	private function register_block_assets() {
		
		// === FRONTEND ONLY ASSETS ===
		
		// GLightbox + listing scripts are registered in WPDINO_Portfolio_Display::register_listing_assets().
		if ( class_exists( 'WPDINO_Portfolio_Display' ) ) {
			WPDINO_Portfolio_Display::get_instance()->register_listing_assets();
		}

		$glightbox_style = WPDINO_Portfolio_Display::get_glightbox_style_handle();
		$plyr_style      = WPDINO_Portfolio_Display::get_plyr_style_handle();
		$lightbox_script = WPDINO_Portfolio_Display::get_portfolio_lightbox_script_handle();

		// Frontend styles (compiled from style.scss) - loads only on frontend when block is used
		wp_register_style(
			'wpdino-portfolio-dinofolio-style',
			DINOFOLIO_URL . 'build/dinofolio.css',
			array( $plyr_style, $glightbox_style ),
			DINOFOLIO_VERSION
		);

		// Frontend script (lightbox) - loads only on frontend when block is used
		wp_register_script(
			'wpdino-portfolio-dinofolio-frontend',
			DINOFOLIO_URL . 'assets/js/portfolio-lightbox.js',
			array(
				WPDINO_Portfolio_Display::get_glightbox_script_handle(),
				WPDINO_Portfolio_Display::get_plyr_script_handle(),
			),
			DINOFOLIO_VERSION,
			true
		);
		
		// === EDITOR ONLY ASSETS ===
		
		// Editor styles (compiled from editor.scss) - loads only in editor
		wp_register_style(
			'wpdino-portfolio-dinofolio-editor-style',
			DINOFOLIO_URL . 'build/editor-dinofolio.css',
			array(),
			DINOFOLIO_VERSION
		);
		
		// Note: Editor script is registered in enqueue_block_editor_assets() for localization
	}

	/**
	 * Get block attributes schema.
	 */
	public function get_block_attributes() {
		return array(
			'align' => array(
				'type'    => 'string',
				'default' => 'center',
			),
			'source' => array(
				'type'    => 'string',
				'default' => 'portfolio_item',
			),
			'layout' => array(
				'type'    => 'string',
				'default' => 'grid',
			),
			'orderBy' => array(
				'type'    => 'string',
				'default' => 'menu_order date',
			),
			'order' => array(
				'type'    => 'string',
				'default' => 'desc',
			),
			'columnsAmount' => array(
				'type'    => 'number',
				'default' => 3,
			),
			'columnsGap' => array(
				'type'    => 'number',
				'default' => 30,
			),
			'showAuthor' => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'showDate' => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'showExcerpt' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showReadMore' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'readMoreLabel' => array(
				'type'    => 'string',
				'default' => esc_html__( 'Read More', 'dinofolio' ),
			),
			'showViewAll' => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'viewAllLabel' => array(
				'type'    => 'string',
				'default' => esc_html__( 'View All', 'dinofolio' ),
			),
			'viewAllLink' => array(
				'type'    => 'string',
				'default' => '',
			),
			'showThumbnail' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'thumbnailSize' => array(
				'type'    => 'string',
				'default' => 'large',
			),
			'showTitle' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'postTitleColor' => array(
				'type'    => 'string',
				'default' => '',
			),
			'postHoverTitleColor' => array(
				'type'    => 'string',
				'default' => '',
			),
			'enableParallax' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'btnTextColor' => array(
				'type'    => 'string',
				'default' => '',
			),
			'btnHoverTextColor' => array(
				'type'    => 'string',
				'default' => '',
			),
			'btnBgColor' => array(
				'type'    => 'string',
				'default' => '',
			),
			'btnHoverBgColor' => array(
				'type'    => 'string',
				'default' => '',
			),
			'lightbox' => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'lightboxCaption' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'secondaryColor' => array(
				'type'    => 'string',
				'default' => '',
			),
			'primaryColor' => array(
				'type'    => 'string',
				'default' => '',
			),
			'filterActiveColor' => array(
				'type'    => 'string',
				'default' => '',
			),
			'layoutBgOpacity' => array(
				'type'    => 'number',
				'default' => 0.8,
			),
			'layoutBgOpacityHover' => array(
				'type'    => 'number',
				'default' => 0.9,
			),
			'enableAjaxLoading' => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'showCategoryFilter' => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'amount' => array(
				'type'    => 'number',
				'default' => 6,
			),
			'categories' => array(
				'type'    => 'array',
				'default' => array(),
			),
			'className' => array(
				'type'    => 'string',
				'default' => '',
			),
		);
	}

	/**
	 * Render the block content.
	 */
	public function render( $block_attributes, $content ) {
		// Use the display class to generate portfolio listing
		return $this->display->render_portfolio_listing( $block_attributes );
	}

	/**
	 * Get the settings
	 */
	public function get_settings() {
		$options = WPDINO_Portfolio_Settings::OPTION_NAME;
		$settings = get_option( $options );

		return $settings;
	}

}