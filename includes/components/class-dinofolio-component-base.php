<?php
/**
 * Component base class.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Base definition for portable DinoFolio components.
 */
abstract class Component_Base {

	/**
	 * Unique component slug.
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Human-readable component title.
	 *
	 * @return string
	 */
	abstract public function get_title();

	/**
	 * Render component output.
	 *
	 * @param array $attributes Component attributes.
	 * @return string
	 */
	abstract public function render( $attributes = array() );

	/**
	 * Unified parameter schema used by all integrations.
	 *
	 * @return array
	 */
	public function get_params() {
		return array();
	}

	/**
	 * Inspector/control section labels keyed by section slug.
	 *
	 * @return array
	 */
	public function get_param_sections() {
		return array(
			'content' => esc_html__( 'Display', 'dinofolio' ),
			'query'   => esc_html__( 'Query', 'dinofolio' ),
		);
	}

	/**
	 * Component description.
	 *
	 * @return string
	 */
	public function get_description() {
		return '';
	}

	/**
	 * Default attributes shared between integrations.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return array();
	}

	/**
	 * Shortcode tags to register for this component.
	 *
	 * @return array
	 */
	public function get_shortcodes() {
		return array( 'dinofolio_' . $this->get_name() );
	}

	/**
	 * Gutenberg block name.
	 *
	 * @return string
	 */
	public function get_block_name() {
		return 'dinofolio/' . str_replace( '_', '-', $this->get_name() );
	}

	/**
	 * Elementor widget name.
	 *
	 * @return string
	 */
	public function get_elementor_widget_name() {
		return 'dinofolio-' . $this->get_name();
	}

	/**
	 * WPBakery shortcode base tag.
	 *
	 * @return string
	 */
	public function get_vc_shortcode_base() {
		$shortcodes = $this->get_shortcodes();

		return isset( $shortcodes[0] ) ? $shortcodes[0] : 'dinofolio_' . $this->get_name();
	}

	/**
	 * Elementor icon.
	 *
	 * @return string
	 */
	public function get_elementor_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * Elementor keywords.
	 *
	 * @return array
	 */
	public function get_elementor_keywords() {
		return array( $this->get_name(), 'dinofolio', 'portfolio' );
	}

	/**
	 * Frontend style handles to enqueue with this component.
	 *
	 * @return array
	 */
	public function get_style_handles() {
		return array();
	}

	/**
	 * Editor-only style handles (block preview in Gutenberg).
	 *
	 * @return array
	 */
	public function get_editor_style_handles() {
		return array();
	}

	/**
	 * WPBakery params definition.
	 *
	 * @return array
	 */
	public function get_vc_params() {
		$params = array();

		foreach ( $this->get_params() as $param ) {
			$params[] = Util::prepare_vc_param( $param );
		}

		return $params;
	}

	/**
	 * Gutenberg attributes schema.
	 *
	 * @return array
	 */
	public function get_block_attributes() {
		return Util::params_to_block_attributes( $this->get_params(), $this->get_defaults() );
	}

	/**
	 * Elementor controls definition.
	 *
	 * @return array
	 */
	public function get_elementor_controls() {
		$controls = array();

		foreach ( $this->get_params() as $param ) {
			$control = Util::prepare_control_args( $param );
			if ( null === $control ) {
				continue;
			}

			$controls[] = array(
				'id'      => $control['name'],
				'options' => $control['args'],
			);
		}

		return $controls;
	}
}
