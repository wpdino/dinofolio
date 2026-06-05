<?php
/**
 * Portfolio component.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Component definition for the portfolio listing.
 */
class Portfolio_Component extends Component_Base {

	/**
	 * Component slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'portfolio';
	}

	/**
	 * Component title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Portfolio Listing', 'dinofolio' );
	}

	/**
	 * Component description.
	 *
	 * @return string
	 */
	public function get_description() {
		return esc_html__( 'Display a filterable portfolio grid, masonry, or list of projects.', 'dinofolio' );
	}

	/**
	 * Shared defaults used by integrations.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return array(
			'layout'         => 'grid',
			'columns'        => 3,
			'postsToShow'    => 12,
			'showTitle'      => true,
			'showCategories' => true,
			'showExcerpt'    => true,
			'showReadMore'   => true,
			'readMoreLabel'  => esc_html__( 'View Project', 'dinofolio' ),
			'imageSize'      => 'large',
			'lightbox'       => true,
			'showFilter'      => false,
			'showFilterCount' => false,
			'showPagination' => true,
			'showViewAll'    => false,
			'viewAllText'    => esc_html__( 'View All', 'dinofolio' ),
			'viewAllLink'    => '',
			'categories'     => array(),
			'tags'           => array(),
			'orderBy'        => 'date',
			'order'          => 'desc',
		);
	}

	/**
	 * Keep legacy shortcode and add explicit component shortcode.
	 *
	 * @return array
	 */
	public function get_shortcodes() {
		return array( 'dinofolio', 'dinofolio_portfolio' );
	}

	/**
	 * Frontend styles for the portfolio listing markup.
	 *
	 * @return array
	 */
	public function get_style_handles() {
		return array( 'dinofolio-portfolio-listing' );
	}

	/**
	 * Editor-only styles for the portfolio block preview.
	 *
	 * @return array
	 */
	public function get_editor_style_handles() {
		return array( 'dinofolio-portfolio-listing-editor' );
	}

	/**
	 * Unified params for Elementor, Gutenberg, WPBakery, and shortcodes.
	 *
	 * @return array
	 */
	public function get_params() {
		return array(
			array(
				'type'       => 'dropdown',
				'heading'    => esc_html__( 'Layout', 'dinofolio' ),
				'param_name' => 'layout',
				'section'    => 'content',
				'value'      => array(
					'grid'    => esc_html__( 'Grid', 'dinofolio' ),
					'masonry' => esc_html__( 'Masonry', 'dinofolio' ),
					'list'    => esc_html__( 'List', 'dinofolio' ),
				),
				'std'        => 'grid',
			),
			array(
				'type'       => 'number',
				'heading'    => esc_html__( 'Columns', 'dinofolio' ),
				'param_name' => 'columns',
				'section'    => 'content',
				'std'        => 3,
				'min'        => 2,
				'max'        => 4,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => esc_html__( 'Image Size', 'dinofolio' ),
				'param_name' => 'imageSize',
				'section'    => 'content',
				'value'      => Util::get_image_size_options(),
				'std'        => 'large',
			),
			array(
				'type'       => 'checkbox',
				'heading'    => esc_html__( 'Show Title', 'dinofolio' ),
				'param_name' => 'showTitle',
				'section'    => 'content',
				'std'        => 'yes',
			),
			array(
				'type'       => 'checkbox',
				'heading'    => esc_html__( 'Show Categories', 'dinofolio' ),
				'param_name' => 'showCategories',
				'section'    => 'content',
				'std'        => 'yes',
			),
			array(
				'type'       => 'checkbox',
				'heading'    => esc_html__( 'Show Excerpt', 'dinofolio' ),
				'param_name' => 'showExcerpt',
				'section'    => 'content',
				'std'        => 'yes',
			),
			array(
				'type'       => 'checkbox',
				'heading'    => esc_html__( 'Show Read More Button', 'dinofolio' ),
				'param_name' => 'showReadMore',
				'section'    => 'content',
				'std'        => 'yes',
			),
			array(
				'type'       => 'textfield',
				'heading'    => esc_html__( 'Read More Label', 'dinofolio' ),
				'param_name' => 'readMoreLabel',
				'section'    => 'content',
				'std'        => esc_html__( 'View Project', 'dinofolio' ),
			),
			array(
				'type'       => 'checkbox',
				'heading'    => esc_html__( 'Enable Lightbox', 'dinofolio' ),
				'param_name' => 'lightbox',
				'section'    => 'content',
				'std'        => 'yes',
			),
			array(
				'type'        => 'checkbox',
				'heading'     => esc_html__( 'Show Category Filter', 'dinofolio' ),
				'param_name'  => 'showFilter',
				'section'     => 'content',
				'description' => esc_html__( 'Display a filter bar above the listing.', 'dinofolio' ),
				'std'         => 'no',
			),
			array(
				'type'        => 'checkbox',
				'heading'     => esc_html__( 'Show Category Counts', 'dinofolio' ),
				'param_name'  => 'showFilterCount',
				'section'     => 'content',
				'description' => esc_html__( 'Show the number of projects in each filter tab.', 'dinofolio' ),
				'std'         => 'no',
			),
			array(
				'type'       => 'checkbox',
				'heading'    => esc_html__( 'Show Pagination', 'dinofolio' ),
				'param_name' => 'showPagination',
				'section'    => 'content',
				'std'        => 'yes',
			),
			array(
				'type'       => 'checkbox',
				'heading'    => esc_html__( 'Show View All Button', 'dinofolio' ),
				'param_name' => 'showViewAll',
				'section'    => 'content',
				'std'        => 'no',
			),
			array(
				'type'       => 'textfield',
				'heading'    => esc_html__( 'View All Label', 'dinofolio' ),
				'param_name' => 'viewAllText',
				'section'    => 'content',
				'std'        => esc_html__( 'View All', 'dinofolio' ),
			),
			array(
				'type'       => 'textfield',
				'heading'    => esc_html__( 'View All Link', 'dinofolio' ),
				'param_name' => 'viewAllLink',
				'section'    => 'content',
				'std'        => '',
			),
			array(
				'type'        => 'taxonomy',
				'heading'     => esc_html__( 'Categories', 'dinofolio' ),
				'param_name'  => 'categories',
				'section'     => 'query',
				'taxonomy'    => 'wpdino_portfolio_category',
				'hierarchical'=> true,
				'description' => esc_html__( 'Limit results to selected categories. Leave empty to show all.', 'dinofolio' ),
				'std'         => array(),
			),
			array(
				'type'        => 'taxonomy',
				'heading'     => esc_html__( 'Tags', 'dinofolio' ),
				'param_name'  => 'tags',
				'section'     => 'query',
				'taxonomy'    => 'wpdino_portfolio_tag',
				'hierarchical'=> false,
				'description' => esc_html__( 'Limit results to selected tags. Leave empty to show all.', 'dinofolio' ),
				'std'         => array(),
			),
			array(
				'type'       => 'number',
				'heading'    => esc_html__( 'Posts To Show', 'dinofolio' ),
				'param_name' => 'postsToShow',
				'section'    => 'query',
				'std'        => 12,
				'min'        => 1,
				'max'        => 100,
			),
			array(
				'type'       => 'dropdown',
				'heading'    => esc_html__( 'Order By', 'dinofolio' ),
				'param_name' => 'orderBy',
				'section'    => 'query',
				'value'      => array(
					'menu_order' => esc_html__( 'Default (Menu Order)', 'dinofolio' ),
					'date'       => esc_html__( 'Date', 'dinofolio' ),
					'title'      => esc_html__( 'Title', 'dinofolio' ),
					'modified'   => esc_html__( 'Last Modified', 'dinofolio' ),
					'rand'       => esc_html__( 'Random', 'dinofolio' ),
				),
				'std'        => 'date',
			),
			array(
				'type'       => 'dropdown',
				'heading'    => esc_html__( 'Order', 'dinofolio' ),
				'param_name' => 'order',
				'section'    => 'query',
				'value'      => array(
					'desc' => esc_html__( 'Descending', 'dinofolio' ),
					'asc'  => esc_html__( 'Ascending', 'dinofolio' ),
				),
				'std'        => 'desc',
			),
		);
	}

	/**
	 * Render component output.
	 *
	 * @param array $attributes Attributes.
	 * @return string
	 */
	public function render( $attributes = array() ) {
		if ( ! class_exists( '\WPDINO_Portfolio_Display' ) ) {
			return '';
		}

		$attributes = Util::normalize_atts( $attributes, $this );

		return \WPDINO_Portfolio_Display::get_instance()->render_portfolio_listing( $attributes );
	}
}
