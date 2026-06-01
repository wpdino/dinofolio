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

		vc_map(
			array(
				'name'        => $this->component->get_title(),
				'base'        => $this->component->get_vc_shortcode_base(),
				'description' => $this->component->get_description(),
				'category'    => esc_html__( 'DinoFolio', 'dinofolio' ),
				'icon'        => $this->component->get_elementor_icon(),
				'params'      => $this->component->get_vc_params(),
			)
		);
	}
}
