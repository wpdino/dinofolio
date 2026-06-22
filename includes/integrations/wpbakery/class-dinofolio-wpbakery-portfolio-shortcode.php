<?php
/**
 * WPBakery shortcode renderer for the portfolio listing.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Portfolio listing WPBakery element.
 */
class WPBakery_Portfolio_Shortcode extends \WPBakeryShortCode {

	/**
	 * Render portfolio listing output.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	protected function content( $atts, $content = null ) {
		unset( $content );

		$component = Components::instance()->get( 'portfolio' );

		if ( ! $component ) {
			return '';
		}

		$tag  = $this->getShortcode();
		$atts = is_array( $atts ) ? $atts : array();
		$params = $component->get_params();

		// vc_map_get_attributes() expects snake_case keys from vc_map param_name.
		$atts = Util::map_atts_to_vc_param_names( $atts, $params );

		if ( function_exists( 'vc_map_get_attributes' ) ) {
			$atts = vc_map_get_attributes( $tag, $atts );
		}

		$atts = Util::normalize_atts( $atts, $component, 'wpbakery' );

		return $component->render( $atts );
	}
}
