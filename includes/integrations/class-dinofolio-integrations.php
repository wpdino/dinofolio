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
		require_once DINOFOLIO_PATH . 'includes/integrations/class-dinofolio-elementor-integration.php';
		require_once DINOFOLIO_PATH . 'includes/integrations/class-dinofolio-wpbakery-integration.php';

		// Default-first: Gutenberg integration is initialized first.
		Gutenberg_Integration::instance();
		Shortcode_Integration::instance();
		Elementor_Integration::instance();
		WPBakery_Integration::instance();
	}
}

