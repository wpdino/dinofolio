<?php
/**
 * Integrations bootstrap.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Registers all component integrations with builders.
 */
class Integrations {

	/**
	 * Singleton instance.
	 *
	 * @var Integrations|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Integrations
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		require_once DINOFOLIO_PATH . 'includes/class-dinofolio-util.php';
		require_once DINOFOLIO_PATH . 'includes/integrations/class-dinofolio-shortcode-integration.php';
		require_once DINOFOLIO_PATH . 'includes/integrations/class-dinofolio-gutenberg-integration.php';
		require_once DINOFOLIO_PATH . 'includes/integrations/class-dinofolio-wpbakery-integration.php';

		// Default-first: Gutenberg integration is initialized first.
		Gutenberg_Integration::instance();
		Shortcode_Integration::instance();
		$this->init_elementor_integration();
		WPBakery_Integration::instance();

		/**
		 * Fires after DinoFolio builder integrations are bootstrapped.
		 */
		do_action( 'dinofolio_integrations_loaded' );
	}

	/**
	 * Load Elementor integration only when Elementor is available.
	 *
	 * @return void
	 */
	private function init_elementor_integration() {
		add_action( 'elementor/loaded', array( $this, 'boot_elementor_integration' ), 0 );

		if ( did_action( 'elementor/loaded' ) ) {
			$this->boot_elementor_integration();
		}
	}

	/**
	 * Bootstrap Elementor widgets once Elementor core is ready.
	 *
	 * @return void
	 */
	public function boot_elementor_integration() {
		static $booted = false;

		if ( $booted || ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			add_action( 'elementor/init', array( $this, 'boot_elementor_integration' ), 9 );
			return;
		}

		$booted = true;

		require_once DINOFOLIO_PATH . 'includes/integrations/class-dinofolio-elementor-integration.php';
		Elementor_Integration::instance();
	}
}

