<?php
/**
 * Base Elementor widget for DinoFolio components.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Generic Elementor widget wrapper for component definitions.
 */
class Elementor_Widget_Base extends Widget_Base {

	/**
	 * Component slug for this widget type.
	 *
	 * @var string
	 */
	protected $component_slug = '';

	/**
	 * Cached component instance.
	 *
	 * @var Component_Base|null
	 */
	protected $component = null;

	/**
	 * Create a widget type instance for Elementor registration.
	 *
	 * @param Component_Base $component Component instance.
	 * @return static
	 */
	public static function from_component( Component_Base $component ) {
		return new static(
			array(),
			array(
				'component_slug' => $component->get_name(),
			)
		);
	}

	/**
	 * Elementor-compatible constructor.
	 *
	 * @param array      $data Widget data.
	 * @param array|null $args Widget args.
	 */
	public function __construct( $data = array(), $args = null ) {
		$data = $this->normalize_widget_data( $data );

		if ( ! empty( $data ) && null === $args ) {
			$args = array();
		}

		parent::__construct( $data, $args );

		if ( is_array( $args ) && ! empty( $args['component_slug'] ) ) {
			$this->component_slug = sanitize_key( (string) $args['component_slug'] );
		} elseif ( ! empty( $data['widgetType'] ) ) {
			$this->component_slug = $this->slug_from_widget_type( (string) $data['widgetType'] );
		}
	}

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		$component = $this->get_component();

		return $component ? $component->get_elementor_widget_name() : 'dinofolio-portfolio';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		$component = $this->get_component();

		return $component ? $component->get_title() : esc_html__( 'Portfolio Listing', 'dinofolio' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		$component = $this->get_component();

		return $component ? $component->get_elementor_icon() : 'eicon-posts-grid';
	}

	/**
	 * Widget category.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'dinofolio' );
	}

	/**
	 * Widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		$component = $this->get_component();

		return $component ? $component->get_elementor_keywords() : array( 'portfolio', 'dinofolio' );
	}

	/**
	 * Enqueue portfolio listing styles in Elementor editor and frontend.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		$component = $this->get_component();

		return $component ? $component->get_style_handles() : array();
	}

	/**
	 * Register controls from unified component params.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$component = $this->get_component();

		if ( ! $component ) {
			return;
		}

		$params   = $component->get_params();
		$defaults = $component->get_defaults();
		$sections = $component->get_param_sections();

		if ( empty( $params ) ) {
			return;
		}

		$grouped = array();

		foreach ( $sections as $section_key => $section_label ) {
			$grouped[ $section_key ] = array(
				'label'  => $section_label,
				'params' => array(),
			);
		}

		foreach ( $params as $param ) {
			$section_key = isset( $param['section'] ) ? $param['section'] : 'content';

			if ( ! isset( $grouped[ $section_key ] ) ) {
				$grouped[ $section_key ] = array(
					'label'  => ucfirst( $section_key ),
					'params' => array(),
				);
			}

			$grouped[ $section_key ]['params'][] = $param;
		}

		foreach ( $grouped as $section_key => $section ) {
			if ( empty( $section['params'] ) ) {
				continue;
			}

			$this->start_controls_section(
				'section_' . $section_key,
				array(
					'label' => $section['label'],
					'tab'   => Controls_Manager::TAB_CONTENT,
				)
			);

			foreach ( $section['params'] as $param ) {
				$param_name = isset( $param['param_name'] ) ? $param['param_name'] : '';

				if ( ! empty( $param_name ) && ! isset( $param['std'] ) && isset( $defaults[ $param_name ] ) ) {
					$param['std'] = $defaults[ $param_name ];
				}

				$control = Util::prepare_control_args( $param );
				if ( null === $control ) {
					continue;
				}

				$this->add_control( $control['name'], $control['args'] );
			}

			$this->end_controls_section();
		}
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$component = $this->get_component();

		if ( ! $component ) {
			if ( current_user_can( 'edit_posts' ) ) {
				echo '<p>' . esc_html__( 'DinoFolio component is not available.', 'dinofolio' ) . '</p>';
			}
			return;
		}

		$settings = $this->get_settings_for_display();
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$attributes = Util::normalize_atts( $settings, $component, 'elementor' );

		echo $component->render( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @param string|null $item Optional data key.
	 * @return mixed
	 */
	public function get_data( $item = null ) {
		if ( isset( $this->data['settings'] ) && ! is_array( $this->data['settings'] ) ) {
			$this->data['settings'] = array();
		}

		return parent::get_data( $item );
	}

	/**
	 * Resolve the component for this widget type.
	 *
	 * @return Component_Base|null
	 */
	protected function get_component() {
		if ( null !== $this->component ) {
			return $this->component;
		}

		if ( empty( $this->component_slug ) ) {
			$default_slug = $this->get_default_args( 'component_slug' );
			if ( ! empty( $default_slug ) ) {
				$this->component_slug = sanitize_key( (string) $default_slug );
			} else {
				$this->component_slug = $this->slug_from_widget_type( $this->get_name() );
			}
		}

		if ( empty( $this->component_slug ) ) {
			return null;
		}

		$this->component = Components::instance()->get( $this->component_slug );

		return $this->component;
	}

	/**
	 * Extract component slug from Elementor widget type name.
	 *
	 * @param string $widget_type Widget type, e.g. dinofolio-portfolio.
	 * @return string
	 */
	protected function slug_from_widget_type( $widget_type ) {
		return sanitize_key( preg_replace( '/^dinofolio[-_]/', '', $widget_type ) );
	}

	/**
	 * Ensure Elementor always receives an array for settings.
	 *
	 * @param mixed $data Raw widget data.
	 * @return array
	 */
	private function normalize_widget_data( $data ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		if ( empty( $data ) ) {
			return $data;
		}

		if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			$data['settings'] = array();
		}

		return $data;
	}
}
