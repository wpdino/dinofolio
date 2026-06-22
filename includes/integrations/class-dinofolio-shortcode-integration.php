<?php
/**
 * Shortcode integration.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Registers shortcodes for all components.
 */
class Shortcode_Integration {

	/**
	 * Singleton instance.
	 *
	 * @var Shortcode_Integration|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Shortcode_Integration
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
		add_action( 'init', array( $this, 'register_shortcodes' ), 7 );
	}

	/**
	 * Register component shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		foreach ( Components::instance()->all() as $component ) {
			foreach ( $component->get_shortcodes() as $tag ) {
				add_shortcode(
					$tag,
					function( $atts = array() ) use ( $component, $tag ) {
						$atts = Util::normalize_atts( (array) $atts, $component, 'shortcode' );

						/**
						 * Fires before a DinoFolio shortcode is rendered.
						 *
						 * @param array          $atts      Normalized shortcode attributes.
						 * @param string         $tag       Shortcode tag.
						 * @param Component_Base $component Component instance.
						 */
						do_action( 'dinofolio_before_render_shortcode', $atts, $tag, $component );

						$output = $component->render( $atts );

						/**
						 * Filter DinoFolio shortcode output.
						 *
						 * @param string         $output    Rendered HTML.
						 * @param array          $atts      Normalized shortcode attributes.
						 * @param string         $tag       Shortcode tag.
						 * @param Component_Base $component Component instance.
						 */
						return apply_filters( 'dinofolio_shortcode_output', $output, $atts, $tag, $component );
					}
				);
			}
		}
	}
}
