<?php
/**
 * Custom Elementor Controls for DinoPack.
 *
 * @package DinoPack
 * @since   1.2.0
 */

namespace Dinopack;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Instance the plugin.
Dinopack_Elementor_Controls::instance();

/**
* DinoPack Elementor Controls Class
 *
 * @since 1.2.0
 */
class Dinopack_Elementor_Controls {
	/**
	 * Instance
	 *
	 * @var Dinopack_Elementor_Controls The single instance of the class.
	 * @since 1.2.0
	 * @access private
	 * @static
	 */
	private static $_instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.2.0
	 * @access public
	 * @static
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
	 * @since 1.2.0
	 * @access public
	 */
	public function __construct() {
		add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );
	}

	/**
	 * Register custom controls.
	 *
	 * @since  1.2.0
	 * @access public
	 * @param  \Elementor\Controls_Manager $controls_manager Elementor controls manager.
	 */
	public function register_controls( $controls_manager ) {

        $controls = array(
			'wpdino_select_image' => array(
				'file'  => __DIR__ . '/controls/class-control-select-image.php',
				'class' => 'Controls\WPDino_Select_Image',
			),

		);

		foreach ( $controls as $control_type => $control_info ) {
			if ( ! empty( $control_info['file'] ) && ! empty( $control_info['class'] ) ) {
				include_once $control_info['file'];
				if ( class_exists( $control_info['class'] ) ) {
					$class_name = $control_info['class'];
				} elseif ( class_exists( __NAMESPACE__ . '\\' . $control_info['class'] ) ) {
					$class_name = __NAMESPACE__ . '\\' . $control_info['class'];
				}
				$controls_manager->register( new $class_name() );
			}
		}

	}
}
