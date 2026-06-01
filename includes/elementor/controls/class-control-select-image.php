<?php

namespace Dinopack\Controls;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Plugin;
use Elementor\Base_Data_Control;

/**
 * Elementor tagfield control.
 *
 * A base control for creating an icon control. Displays a font icon select box
 * field. The control accepts `include` or `exclude` arguments to set a partial
 * list of icons.
 *
 * @since 1.0.0
 */
class WPDINO_Select_Image extends Base_Data_Control {

	/**
	 * Get icon control type.
	 *
	 * Retrieve the control type, in this case `icon`.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Control type.
	 */
	public function get_type() {
		return 'wpdino_select_image';
	}

	public function enqueue() {

		// Styles
		wp_register_style( 
			'wpdino-select-image', 
			DINOPACK_URL . 'inc/elementor/controls/assets/js/vendors/image-picker/image-picker.css', 
			array(), 
			DINOPACK_VERSION 
		);
		
		wp_register_script( 
			'wpdino-select-image', 
			DINOPACK_URL . 'inc/elementor/controls/assets/js/vendors/image-picker/image-picker.js', 
			array( 'jquery' ), 
			DINOPACK_VERSION, 
			true 
		);
		
		wp_register_script( 
			'wpdino-select-image-control', 
			DINOPACK_URL . 'inc/elementor/controls/assets/js/image-picker-control.js', 
			array( 'jquery' ), 
			DINOPACK_VERSION, 
			true 
		);

		wp_enqueue_style( 'wpdino-select-image' );
		wp_enqueue_style( 'wpdino-select-image' );
		wp_enqueue_script( 'wpdino-select-image' );
		wp_enqueue_script( 'wpdino-select-image-control' );
	}

	/**
	 * Get select control default settings.
	 *
	 * Retrieve the default settings of the select control. Used to return the
	 * default settings while initializing the select control.
	 *
	 * @since 2.0.0
	 * @access protected
	 *
	 * @return array Control default settings.
	 */
	protected function get_default_settings() {
		return array(
			'options' => array(),
		);
	}

	/**
	 * Render select control output in the editor.
	 *
	 * Used to generate the control HTML in the editor using Underscore JS
	 * template. The variables for the class are available using `data` JS
	 * object.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function content_template() {
		?>
		<div class="elementor-control-field">
			<# if ( data.label ) {#>
				<label for="<?php $this->print_control_uid(); ?>" class="elementor-control-title">{{{ data.label }}}</label>
			<# } #>
			<div class="elementor-control-input-wrapper elementor-control-unit-5">
				<select id="<?php $this->print_control_uid(); ?>" data-setting="{{ data.name }}">
				<#
					var printOptions = function( options ) {
						_.each( options, function( option_data, option_value ) { #>
								<option data-img-label="{{ option_data.label }}" data-img-src="{{ option_data.image }}" value="{{ option_value }}">{{{ option_data.label }}}</option>
						<# } );
					};

					if ( data.groups ) {
						for ( var groupIndex in data.groups ) {
							var groupArgs = data.groups[ groupIndex ];
								if ( groupArgs.options ) { #>
									<optgroup label="{{ groupArgs.label }}">
										<# printOptions( groupArgs.options ) #>
									</optgroup>
								<# } else if ( _.isString( groupArgs ) ) { #>
									<option value="{{ groupIndex }}">{{{ groupArgs }}}</option>
								<# }
						}
					} else {
						printOptions( data.options );
					}
				#>
				</select>
			</div>
		</div>
		<# if ( data.description ) { #>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
		<# } #>
		<?php
	}

}
