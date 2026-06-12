<?php
/**
 * Portfolio Listing Widget
 *
 * @package DinoFolio
 * @since 1.0.0
 */

namespace DinoFolio\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Portfolio listing widget.
 *
 * @since 1.0.0
 */
class Portfolio_Listing extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'dinofolio-portfolio-listing';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Portfolio Listing', 'dinofolio' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * Get widget categories.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'dinofolio' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'portfolio', 'projects', 'listing', 'dinofolio' );
	}

	/**
	 * Register widget controls.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @return void
	 */
	protected function register_controls() {
		// Content tab.
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Content', 'dinofolio' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'dinofolio' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => array(
					'grid'    => esc_html__( 'Grid', 'dinofolio' ),
					'masonry' => esc_html__( 'Masonry', 'dinofolio' ),
				),
			)
		);

		$this->add_control(
			'style',
			array(
				'label'   => esc_html__( 'Style', 'dinofolio' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'standard',
				'options' => array(
					'standard' => esc_html__( 'Standard', 'dinofolio' ),
					'overlay'  => esc_html__( 'Overlay', 'dinofolio' ),
				),
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => esc_html__( 'Columns', 'dinofolio' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '3',
				'options' => array(
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
			)
		);

		$this->add_control(
			'posts_to_show',
			array(
				'label'   => esc_html__( 'Posts To Show', 'dinofolio' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 12,
				'min'     => 1,
				'max'     => 100,
			)
		);

		$this->add_control(
			'show_excerpt',
			array(
				'label'        => esc_html__( 'Show Excerpt', 'dinofolio' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'dinofolio' ),
				'label_off'    => esc_html__( 'Hide', 'dinofolio' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_pagination',
			array(
				'label'        => esc_html__( 'Show Pagination', 'dinofolio' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'dinofolio' ),
				'label_off'    => esc_html__( 'Hide', 'dinofolio' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'order_by',
			array(
				'label'   => esc_html__( 'Order By', 'dinofolio' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'  => esc_html__( 'Date', 'dinofolio' ),
					'title' => esc_html__( 'Title', 'dinofolio' ),
					'rand'  => esc_html__( 'Random', 'dinofolio' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Order', 'dinofolio' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'desc',
				'options' => array(
					'desc' => esc_html__( 'Descending', 'dinofolio' ),
					'asc'  => esc_html__( 'Ascending', 'dinofolio' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on the frontend.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @return void
	 */
	protected function render() {
		$settings   = $this->get_settings_for_display();
		$attributes = $this->build_render_attributes( $settings );

		// Delegate final markup rendering to the shared display renderer.
		if ( class_exists( '\\WPDINO_Portfolio_Display' ) ) {
			echo \WPDINO_Portfolio_Display::get_instance()->render_portfolio_listing( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		echo '<p>' . esc_html__( 'Portfolio display module is not available.', 'dinofolio' ) . '</p>';
	}

	/**
	 * Build sanitized attributes for the shared portfolio renderer.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array $settings Widget settings.
	 * @return array
	 */
	private function build_render_attributes( $settings ) {
		$layout     = ! empty( $settings['layout'] ) ? sanitize_key( $settings['layout'] ) : 'grid';
		$style      = ! empty( $settings['style'] ) ? sanitize_key( $settings['style'] ) : 'standard';
		$order_by   = ! empty( $settings['order_by'] ) ? sanitize_key( $settings['order_by'] ) : 'date';
		$order      = ! empty( $settings['order'] ) ? strtoupper( sanitize_key( $settings['order'] ) ) : 'DESC';
		$columns    = ! empty( $settings['columns'] ) ? absint( $settings['columns'] ) : 3;
		$post_count = ! empty( $settings['posts_to_show'] ) ? absint( $settings['posts_to_show'] ) : 12;

		$allowed_layouts  = array( 'grid', 'masonry' );
		$allowed_styles   = array( 'standard', 'overlay' );
		$allowed_order_by = array( 'date', 'title', 'rand' );
		$allowed_order    = array( 'ASC', 'DESC' );
		$allowed_columns  = array( 2, 3, 4 );

		if ( ! in_array( $layout, $allowed_layouts, true ) ) {
			$layout = 'grid';
		}

		if ( 'overlay' === $layout || 'list' === $layout ) {
			$layout = 'grid';
			$style  = 'overlay';
		}

		if ( 'classic' === $style ) {
			$style = 'standard';
		}

		if ( ! in_array( $style, $allowed_styles, true ) ) {
			$style = 'standard';
		}

		if ( ! in_array( $order_by, $allowed_order_by, true ) ) {
			$order_by = 'date';
		}

		if ( ! in_array( $order, $allowed_order, true ) ) {
			$order = 'DESC';
		}

		if ( ! in_array( $columns, $allowed_columns, true ) ) {
			$columns = 3;
		}

		if ( $post_count < 1 ) {
			$post_count = 12;
		}

		return array(
			'layout'         => $layout,
			'style'          => $style,
			'columns'        => $columns,
			'postsToShow'    => $post_count,
			'showExcerpt'    => ( isset( $settings['show_excerpt'] ) && 'yes' === $settings['show_excerpt'] ),
			'showPagination' => ( isset( $settings['show_pagination'] ) && 'yes' === $settings['show_pagination'] ),
			'orderBy'        => $order_by,
			'order'          => $order,
		);
	}
}
