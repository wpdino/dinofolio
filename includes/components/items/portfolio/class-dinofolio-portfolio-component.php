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
		return esc_html__( 'Display a filterable portfolio grid or masonry gallery with standard or overlay cards.', 'dinofolio' );
	}

	/**
	 * Shared defaults used by integrations.
	 *
	 * @return array
	 */
	protected function define_defaults() {
		return array(
			'layout'          => 'grid',
			'columns'         => 3,
			'postsToShow'     => 12,
			'showTitle'       => true,
			'showCategories'  => true,
			'showExcerpt'     => true,
			'excerptLength'   => 120,
			'showReadMore'    => true,
			'readMoreLabel'   => esc_html__( 'View Project', 'dinofolio' ),
			'readMoreAlign'   => 'right',
			'imageSize'       => 'large',
			'lightbox'        => true,
			'showFilter'      => false,
			'showFilterCount' => false,
			'paginationMode'  => 'pagination',
			'loadMoreLabel'   => esc_html__( 'Load More', 'dinofolio' ),
			'loadMoreTrigger' => 'click',
			'showViewAll'     => false,
			'viewAllText'     => esc_html__( 'View All', 'dinofolio' ),
			'viewAllLink'     => '',
			'categories'      => array(),
			'tags'            => array(),
			'orderBy'         => 'date',
			'order'           => 'desc',
			'style'           => 'standard',
			'hoverEffect'     => 'zoom',
			'accentColor'     => '#1a8960',
			'hoverColor'      => '',
			'buttonTextColor' => '',
			'mutedColor'      => '',
			'gap'                 => 24,
			'radius'              => 10,
			'enableParallax'      => false,
		);
	}

	/**
	 * Inspector section labels.
	 *
	 * @return array
	 */
	protected function define_param_sections() {
		return array(
			'content' => esc_html__( 'Display', 'dinofolio' ),
			'style'   => esc_html__( 'Style', 'dinofolio' ),
			'query'   => esc_html__( 'Query', 'dinofolio' ),
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
	protected function define_style_handles() {
		return array( 'dinofolio-portfolio-listing' );
	}

	/**
	 * Editor-only styles for the portfolio block preview.
	 *
	 * @return array
	 */
	protected function define_editor_style_handles() {
		return array( 'dinofolio-portfolio-listing', 'dinofolio-portfolio-listing-editor' );
	}

	/**
	 * Unified params for Elementor, Gutenberg, WPBakery, and shortcodes.
	 *
	 * @return array
	 */
	protected function define_component_params() {
		return array(
			array(
				'type'       => 'dropdown',
				'heading'    => esc_html__( 'Layout', 'dinofolio' ),
				'param_name' => 'layout',
				'section'    => 'content',
				'value'      => array(
					'grid'    => esc_html__( 'Grid', 'dinofolio' ),
					'masonry' => esc_html__( 'Masonry', 'dinofolio' ),
				),
				'std'        => 'grid',
			),
			array(
				'type'       => 'dropdown',
				'heading'    => esc_html__( 'Style', 'dinofolio' ),
				'param_name' => 'style',
				'section'    => 'content',
				'value'      => array(
					'standard' => esc_html__( 'Standard', 'dinofolio' ),
					'overlay'  => esc_html__( 'Overlay', 'dinofolio' ),
				),
				'std'        => 'standard',
			),
			array(
				'type'        => 'dropdown',
				'heading'     => esc_html__( 'Columns', 'dinofolio' ),
				'param_name'  => 'columns',
				'section'     => 'content',
				'description' => esc_html__( 'Only 2, 3, or 4 columns are supported in this listing.', 'dinofolio' ),
				'value'       => array(
					2 => '2',
					3 => '3',
					4 => '4',
				),
				'std'         => 3,
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
				'type'        => 'number',
				'heading'     => esc_html__( 'Excerpt Character Limit', 'dinofolio' ),
				'param_name'  => 'excerptLength',
				'section'     => 'content',
				'description' => esc_html__( 'Maximum number of characters shown in the excerpt.', 'dinofolio' ),
				'std'         => 120,
				'min'         => 20,
				'max'         => 1000,
				'dependency'  => array(
					'element' => 'showExcerpt',
					'value'   => array( true, 'true', '1', 1, 'yes' ),
				),
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
				'dependency' => array(
					'element' => 'showReadMore',
					'value'   => array( true, 'true', '1', 1, 'yes' ),
				),
			),
			array(
				'type'       => 'dropdown',
				'heading'    => esc_html__( 'Read More Alignment', 'dinofolio' ),
				'param_name' => 'readMoreAlign',
				'section'    => 'content',
				'value'      => array(
					'left'   => esc_html__( 'Left', 'dinofolio' ),
					'center' => esc_html__( 'Center', 'dinofolio' ),
					'right'  => esc_html__( 'Right', 'dinofolio' ),
				),
				'std'        => 'right',
				'dependency' => array(
					'element' => 'showReadMore',
					'value'   => array( true, 'true', '1', 1, 'yes' ),
				),
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
				'type'        => 'checkbox',
				'heading'     => esc_html__( 'Image Parallax', 'dinofolio' ),
				'param_name'  => 'enableParallax',
				'section'     => 'content',
				'description' => esc_html__( 'Subtle parallax movement on portfolio thumbnails.', 'dinofolio' ),
				'std'         => 'no',
			),
			array(
				'type'       => 'dropdown',
				'heading'    => esc_html__( 'Pagination Type', 'dinofolio' ),
				'param_name' => 'paginationMode',
				'section'    => 'content',
				'value'      => array(
					'none'       => esc_html__( 'None', 'dinofolio' ),
					'pagination' => esc_html__( 'Pagination', 'dinofolio' ),
					'load_more'  => esc_html__( 'Load More (AJAX)', 'dinofolio' ),
				),
				'std'        => 'pagination',
			),
			array(
				'type'       => 'textfield',
				'heading'    => esc_html__( 'Load More Label', 'dinofolio' ),
				'param_name' => 'loadMoreLabel',
				'section'    => 'content',
				'std'        => esc_html__( 'Load More', 'dinofolio' ),
				'dependency' => array(
					'element' => 'paginationMode',
					'value'   => 'load_more',
				),
			),
			array(
				'type'        => 'dropdown',
				'heading'     => esc_html__( 'Load More Trigger', 'dinofolio' ),
				'param_name'  => 'loadMoreTrigger',
				'section'     => 'content',
				'description' => esc_html__( 'Load items when the button is clicked or automatically when the loader enters the viewport.', 'dinofolio' ),
				'value'       => array(
					'click'   => esc_html__( 'On Button Click', 'dinofolio' ),
					'in_view' => esc_html__( 'When In Viewport', 'dinofolio' ),
				),
				'std'         => 'click',
				'dependency'  => array(
					'element' => 'paginationMode',
					'value'   => 'load_more',
				),
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
				'type'        => 'number',
				'heading'     => esc_html__( 'Posts To Show', 'dinofolio' ),
				'param_name'  => 'postsToShow',
				'section'     => 'query',
				'description' => esc_html__( 'Number of portfolio items displayed per page.', 'dinofolio' ),
				'std'         => 12,
				'min'         => 1,
				'max'         => 100,
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
			array(
				'type'       => 'number',
				'heading'    => esc_html__( 'Columns Gap', 'dinofolio' ),
				'param_name' => 'gap',
				'section'    => 'style',
				'std'        => 24,
				'min'        => 0,
				'max'        => 80,
			),
			array(
				'type'       => 'number',
				'heading'    => esc_html__( 'Border Radius', 'dinofolio' ),
				'param_name' => 'radius',
				'section'    => 'style',
				'std'        => 10,
				'min'        => 0,
				'max'        => 40,
			),
			array(
				'type'       => 'colorpicker',
				'heading'    => esc_html__( 'Accent Color', 'dinofolio' ),
				'param_name' => 'accentColor',
				'section'    => 'style',
				'std'        => '#1a8960',
			),
			array(
				'type'       => 'colorpicker',
				'heading'    => esc_html__( 'Button Hover Color', 'dinofolio' ),
				'param_name' => 'hoverColor',
				'section'    => 'style',
				'std'        => '',
			),
			array(
				'type'       => 'colorpicker',
				'heading'    => esc_html__( 'Button Text Color', 'dinofolio' ),
				'param_name' => 'buttonTextColor',
				'section'    => 'style',
				'std'        => '',
			),
			array(
				'type'       => 'colorpicker',
				'heading'    => esc_html__( 'Muted Text Color', 'dinofolio' ),
				'param_name' => 'mutedColor',
				'section'    => 'style',
				'std'        => '',
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

		$output = \WPDINO_Portfolio_Display::get_instance()->render_portfolio_listing( $attributes );

		/**
		 * Filter portfolio component render output.
		 *
		 * @param string $output     Rendered HTML.
		 * @param array  $attributes Normalized attributes.
		 * @param self   $component  Component instance.
		 */
		return apply_filters( 'dinofolio_portfolio_component_output', $output, $attributes, $this );
	}
}
