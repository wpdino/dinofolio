<?php
/**
 * Base WPBakery module for DinoFolio components.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Generic WPBakery module wrapper for component definitions.
 */
class WPBakery_Module_Base {

	/**
	 * Component instance.
	 *
	 * @var Component_Base
	 */
	protected $component;

	/**
	 * Constructor.
	 *
	 * @param Component_Base $component Component instance.
	 */
	public function __construct( Component_Base $component ) {
		$this->component = $component;
	}

	/**
	 * Register the WPBakery shortcode map.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'vc_map' ) || ! $this->component ) {
			return;
		}

		$params = $this->component->get_vc_params();

		/**
		 * Filter WPBakery vc_map params for a DinoFolio component.
		 *
		 * @param array          $params    WPBakery params.
		 * @param Component_Base $component Component instance.
		 */
		$params = apply_filters( 'dinofolio_wpbakery_vc_params', $params, $this->component );

		$map = array(
			'name'        => $this->component->get_title(),
			'base'        => $this->component->get_vc_shortcode_base(),
			'description' => $this->component->get_description(),
			'category'    => esc_html__( 'DinoFolio', 'dinofolio' ),
			'icon'        => $this->component->get_elementor_icon(),
			'params'      => $params,
		);

		/**
		 * Filter full WPBakery vc_map arguments for a DinoFolio component.
		 *
		 * @param array          $map       vc_map arguments.
		 * @param Component_Base $component Component instance.
		 */
		$map = apply_filters( 'dinofolio_wpbakery_vc_map', $map, $this->component );

		vc_map( $map );

		/**
		 * Fires after a DinoFolio component is registered in WPBakery.
		 *
		 * @param Component_Base $component Component instance.
		 */
		do_action( 'dinofolio_register_wpbakery_module', $this->component );
	}
}
