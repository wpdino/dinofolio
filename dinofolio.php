<?php
/**
 * Plugin Name: DinoFolio Lite
 * Plugin URI:  https://www.wpdino.com/plugins/dinofolio/
 * Description: The ultimate solution for creatives, designers, photographers, and businesses to showcase their work in a clean, customizable, and professional way. Powered by custom post types, Gutenberg blocks, and flexible templates.
 * Version:     1.0.0
 * Author:      WPDINO
 * Author URI:  https://www.wpdino.com
 * Requires at least:   6.6
 * Tested up to:        7.0
 * Requires PHP: 7.0
 * License:     GPL-2.0+ or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt	
 * Text Domain: dinofolio
 * Domain Path: /languages/
 */

namespace DinoFolio;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'DINOFOLIO_VERSION' ) ) {
	define( 'DINOFOLIO_VERSION', get_file_data( __FILE__, [ 'Version' ] )[0] );
}

define( 'DINOFOLIO__FILE__', __FILE__ );
define( 'DINOFOLIO_PLUGIN_BASE', plugin_basename( DINOFOLIO__FILE__ ) );
define( 'DINOFOLIO_PLUGIN_DIR', dirname( DINOFOLIO_PLUGIN_BASE ) );

define( 'DINOFOLIO_PATH', plugin_dir_path( DINOFOLIO__FILE__ ) );
define( 'DINOFOLIO_URL', plugin_dir_url( DINOFOLIO__FILE__ ) );

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
final class Plugin {	

	/**
	 * Instance
	 *
	 * @var DinoFolio\Plugin The single instance of the class.
	 * @since 1.0.0
	 * @access private
	 * @static
	 */
	private static $_instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return DinoFolio\Plugin An instance of the class.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

    /**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {

		self::includes();

		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
		add_action( 'init', array( $this, 'register_image_sizes' ) );
		add_action( 'init', array( $this, 'register_assets' ) );

		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'plugin_css' ) );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'plugin_css' ) );

	}

	/**
	 * Includes files
	 * @method includes
	 *
	 * @return void
	 */
	public function includes() {

		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-hooks.php';
		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-admin-menus.php';
		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-custom-post.php';
		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-portfolio-category-icon.php';
		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-legacy-settings.php';
		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-display.php';
		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-template.php';
		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-portfolio-video.php';
		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-portfolio-video-thumb.php';
		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-portfolio-video-admin.php';
		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-portfolio-meta-boxes.php';
		require_once DINOFOLIO_PATH . 'includes/components/class-dinofolio-component-base.php';
		require_once DINOFOLIO_PATH . 'includes/components/class-dinofolio-components.php';
		require_once DINOFOLIO_PATH . 'includes/integrations/class-dinofolio-integrations.php';

		// Admin page with settings
		require_once DINOFOLIO_PATH . 'includes/admin/settings/class-dinofolio-settings-page.php';
		require_once DINOFOLIO_PATH . 'includes/admin/settings/class-dinofolio-field-renderer.php';

		// Initialize settings page
		if ( class_exists( '\DinoFolio\DinoFolio_Settings' ) ) {
			\DinoFolio\DinoFolio_Settings::instance();
		}

		if ( class_exists( '\DinoFolio\Components' ) ) {
			\DinoFolio\Components::instance();
		}

		if ( class_exists( '\DinoFolio\Integrations' ) ) {
			\DinoFolio\Integrations::instance();
		}

		if ( class_exists( '\DinoFolio\Template' ) ) {
			\DinoFolio\Template::instance();
		}

	}

	/**
	 * Register shared plugin assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'dinofolio-elementor',
			DINOFOLIO_URL . 'assets/css/dinofolio-elementor.css',
			array(),
			DINOFOLIO_VERSION
		);

		if ( class_exists( 'WPDINO_Portfolio_Display' ) ) {
			\WPDINO_Portfolio_Display::get_instance()->register_listing_assets();
		}
	}

	/**
	 * Enqueue plugin styles.
	 */
	public function plugin_css() {
		wp_enqueue_style( 'dinofolio-elementor' );
	}

	/**
	 * Register plugin image sizes.
	 *
	 * @return void
	 */
	public function register_image_sizes() {
		add_image_size( 'dinofolio-featured-1200x900', 1200, 900, true );
		add_image_size( 'dinofolio-gallery-slider', 1600, 900, true );
	}

	/**
	 * On Plugins Loaded
	 *
	 * Checks if Elementor has loaded, and performs some compatibility checks.
	 * If All checks pass, inits the plugin.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function on_plugins_loaded() {
		/**
		 * Fires after DinoFolio Lite is loaded.
		 *
		 * Pro should bootstrap on this hook.
		 */
		do_action( 'dinofolio_loaded' );
	}

    /**
	 * Initialize the plugin
	 *
	 * Load the plugin only after Elementor (and other plugins) are loaded.
	 * Load the files required to run the plugin.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init() {}
    
}

Plugin::instance();