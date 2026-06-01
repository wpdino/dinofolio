<?php
/**
 * WPBakery integration.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Registers component modules in WPBakery when available.
 */
class WPBakery_Integration {

	/**
	 * Singleton instance.
	 *
	 * @var WPBakery_Integration|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return WPBakery_Integration
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
		add_action( 'vc_before_init', array( $this, 'register_modules' ) );
	}

	/**
	 * Register vc_map modules from components.
	 *
	 * @return void
	 */
	public function register_modules() {
		if ( ! function_exists( 'vc_map' ) ) {
			return;
		}

		require_once DINOFOLIO_PATH . 'includes/integrations/wpbakery/class-dinofolio-wpbakery-module-base.php';

		foreach ( Components::instance()->all() as $component_name => $component ) {
			$module_file  = $this->get_module_file( $component_name );
			$module_class = $this->get_module_class_name( $component_name );

			if ( file_exists( $module_file ) ) {
				require_once $module_file;

				if ( class_exists( $module_class ) ) {
					$module = new $module_class( $component );
					$module->register();
					continue;
				}
			}

			$module = new WPBakery_Module_Base( $component );
			$module->register();
		}
	}

	/**
	 * Get module file path for a component.
	 *
	 * @param string $component_name Component slug.
	 * @return string
	 */
	private function get_module_file( $component_name ) {
		return DINOFOLIO_PATH . 'includes/integrations/wpbakery/modules/' . $component_name . '/module.php';
	}

	/**
	 * Get module class name for a component.
	 *
	 * @param string $component_name Component slug.
	 * @return string
	 */
	private function get_module_class_name( $component_name ) {
		return __NAMESPACE__ . '\\WPBakery_' . Util::slug_to_class_suffix( $component_name ) . '_Module';
	}
}
