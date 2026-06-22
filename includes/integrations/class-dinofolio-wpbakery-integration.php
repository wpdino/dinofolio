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
		add_action( 'vc_before_init', array( $this, 'register_autocomplete_hooks' ), 20 );
		add_filter( 'the_content', array( $this, 'sanitize_portfolio_shortcodes_in_content' ), 9 );
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
	 * Register WPBakery autocomplete callbacks for taxonomy params.
	 *
	 * @return void
	 */
	public function register_autocomplete_hooks() {
		foreach ( Components::instance()->all() as $component ) {
			$tag = $component->get_wpbakery_shortcode_base();

			foreach ( $component->get_params() as $param ) {
				$type = isset( $param['type'] ) ? $param['type'] : '';

				if ( 'taxonomy' !== $type ) {
					continue;
				}

				$param_name = isset( $param['param_name'] ) ? $param['param_name'] : '';
				$taxonomy   = isset( $param['taxonomy'] ) ? $param['taxonomy'] : '';

				if ( empty( $param_name ) || empty( $taxonomy ) ) {
					continue;
				}

				$this->register_taxonomy_autocomplete_hook( $tag, Util::get_vc_param_name( $param_name ), $taxonomy );
			}
		}
	}

	/**
	 * Register autocomplete suggestion/render hooks for one taxonomy param.
	 *
	 * @param string $tag        Shortcode base.
	 * @param string $param_name Param name.
	 * @param string $taxonomy   Taxonomy slug.
	 * @return void
	 */
	private function register_taxonomy_autocomplete_hook( $tag, $param_name, $taxonomy ) {
		$callback_hook = 'vc_autocomplete_' . $tag . '_' . $param_name . '_callback';
		$render_hook   = 'vc_autocomplete_' . $tag . '_' . $param_name . '_render';

		add_filter(
			$callback_hook,
			static function ( $query ) use ( $taxonomy ) {
				return Util::vc_autocomplete_taxonomy_suggestions( $query, $taxonomy );
			}
		);

		add_filter(
			$render_hook,
			static function ( $data ) use ( $taxonomy ) {
				return Util::vc_autocomplete_taxonomy_render( $data, $taxonomy );
			}
		);
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

	/**
	 * Clean portfolio shortcode attributes before WordPress parses them on the frontend.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function sanitize_portfolio_shortcodes_in_content( $content ) {
		if ( ! is_string( $content ) || false === strpos( $content, '[dinofolio_portfolio' ) ) {
			return $content;
		}

		return Util::sanitize_wpbakery_portfolio_shortcodes_in_content( $content );
	}
}
