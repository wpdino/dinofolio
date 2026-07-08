<?php
/**
 * Base Gutenberg block for DinoFolio components.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Generic dynamic block wrapper for component definitions.
 */
class Gutenberg_Block_Base {

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
	 * Register the block.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'register_block_type' ) || ! $this->component ) {
			return;
		}

		$slug          = $this->component->get_name();
		$editor_script = 'dinofolio-block-' . $slug;
		$script_path   = DINOFOLIO_PATH . 'includes/integrations/gutenberg/blocks/' . $slug . '/block.js';

		if ( file_exists( $script_path ) ) {
			wp_register_script(
				$editor_script,
				DINOFOLIO_URL . 'includes/integrations/gutenberg/blocks/' . $slug . '/block.js',
				array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-data' ),
				DINOFOLIO_VERSION,
				true
			);

			wp_localize_script(
				$editor_script,
				'dinofolioBlockConfig_' . str_replace( '-', '_', $slug ),
				apply_filters(
					'dinofolio_block_editor_config',
					array(
						'sections' => $this->component->get_param_sections(),
						'controls' => Util::params_to_editor_controls(
							$this->component->get_params(),
							$this->component->get_defaults()
						),
					),
					$slug,
					$this->component
				)
			);
		}

		$args = array(
			'api_version'     => 3,
			'attributes'      => $this->component->get_block_attributes(),
			'render_callback' => array( $this, 'render' ),
			'title'           => $this->component->get_title(),
			'description'     => $this->component->get_description(),
			'category'        => 'dinofolio',
			'icon'            => 'portfolio',
			'supports'        => array(
				'html'            => false,
				'align'           => false,
				'customClassName' => false,
			),
		);

		$style_handles = $this->component->get_style_handles();
		if ( ! empty( $style_handles ) ) {
			$args['style'] = $style_handles[0];
		}

		$editor_style_handles = $this->component->get_editor_style_handles();
		if ( ! empty( $editor_style_handles ) ) {
			$args['editor_style'] = 1 === count( $editor_style_handles )
				? $editor_style_handles[0]
				: $editor_style_handles;
		}

		if ( wp_script_is( $editor_script, 'registered' ) || file_exists( $script_path ) ) {
			$args['editor_script'] = $editor_script;
		}

		/**
		 * Filter Gutenberg block registration arguments.
		 *
		 * @param array          $args      Block type args.
		 * @param string         $slug      Component slug.
		 * @param Component_Base $component Component instance.
		 */
		$args = apply_filters( 'dinofolio_register_block_type_args', $args, $slug, $this->component );

		register_block_type( $this->component->get_block_name(), $args );
	}

	/**
	 * Render block output.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		if ( ! $this->component ) {
			return '';
		}

		$attributes = is_array( $attributes ) ? $attributes : array();
		$schema     = $this->component->get_block_attributes();
		$attributes = Util::sanitize_block_attributes( $attributes, $schema );
		$attributes = Util::normalize_atts( $attributes, $this->component );

		/**
		 * Fires before a DinoFolio block is rendered.
		 *
		 * @param array          $attributes Normalized block attributes.
		 * @param Component_Base $component  Component instance.
		 */
		do_action( 'dinofolio_before_render_block', $attributes, $this->component );

		$output = $this->component->render( $attributes );

		/**
		 * Filter rendered DinoFolio block output.
		 *
		 * @param string         $output     Rendered HTML.
		 * @param array          $attributes Normalized block attributes.
		 * @param Component_Base $component  Component instance.
		 */
		$output = apply_filters( 'dinofolio_render_block_output', $output, $attributes, $this->component );

		if ( class_exists( '\WPDINO_Portfolio_Display' ) && method_exists( '\WPDINO_Portfolio_Display', 'sanitize_rendered_html' ) ) {
			return \WPDINO_Portfolio_Display::sanitize_rendered_html( $output );
		}

		return wp_kses_post( (string) $output );
	}
}
