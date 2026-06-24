<?php
/**
 * DinoFolio taxonomy template loader.
 *
 * @package DinoFolio
 * @since   1.0.0
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the plugin taxonomy archive template when enabled in settings.
 */
class Template {

	/**
	 * Singleton instance.
	 *
	 * @var Template|null
	 */
	private static $instance = null;

	/**
	 * Taxonomy template filename (theme may override via locate_template).
	 *
	 * @var string
	 */
	const TEMPLATE_FILENAME = 'taxonomy-portfolio-category.php';

	/**
	 * Post type archive template filename (theme may override via locate_template).
	 *
	 * @var string
	 */
	const ARCHIVE_TEMPLATE_FILENAME = 'archive-wpdino_portfolio.php';

	/**
	 * Get singleton instance.
	 *
	 * @return Template
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
		add_filter( 'taxonomy_template', array( $this, 'include_taxonomy_template' ), 99 );
		add_filter( 'archive_template', array( $this, 'include_archive_template' ), 99 );
	}

	/**
	 * Portfolio taxonomies that support the plugin template.
	 *
	 * @return string[]
	 */
	public function get_supported_taxonomies() {
		return array(
			'wpdino_portfolio_category',
			'wpdino_portfolio_tag',
		);
	}

	/**
	 * Resolve the taxonomy template path (child theme, parent theme, then plugin).
	 *
	 * @return string|false
	 */
	public function locate_taxonomy_template() {
		$theme_template = locate_template( self::TEMPLATE_FILENAME );

		if ( $theme_template ) {
			return $theme_template;
		}

		$plugin_template = DINOFOLIO_PATH . 'templates/' . self::TEMPLATE_FILENAME;

		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return false;
	}

	/**
	 * Swap in the plugin taxonomy template when enabled.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public function include_taxonomy_template( $template ) {
		$settings = DinoFolio_Settings::instance();

		if ( ! $settings->get_setting( 'taxonomy_use_template', true ) ) {
			return $template;
		}

		$current_term = get_queried_object();

		if ( ! $current_term instanceof \WP_Term ) {
			return $template;
		}

		if ( ! in_array( $current_term->taxonomy, $this->get_supported_taxonomies(), true ) ) {
			return $template;
		}

		$plugin_template = $this->locate_taxonomy_template();

		if ( ! $plugin_template ) {
			return $template;
		}

		return $plugin_template;
	}

	/**
	 * Resolve the portfolio archive template path (child theme, parent theme, then plugin).
	 *
	 * @return string|false
	 */
	public function locate_archive_template() {
		$theme_template = locate_template( self::ARCHIVE_TEMPLATE_FILENAME );

		if ( $theme_template ) {
			return $theme_template;
		}

		$plugin_template = DINOFOLIO_PATH . 'templates/' . self::ARCHIVE_TEMPLATE_FILENAME;

		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return false;
	}

	/**
	 * Swap in the portfolio archive template when enabled.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public function include_archive_template( $template ) {
		if ( ! is_post_type_archive( 'wpdino_portfolio' ) ) {
			return $template;
		}

		$settings = DinoFolio_Settings::instance();

		if ( ! $settings->get_setting( 'taxonomy_use_template', true ) ) {
			return $template;
		}

		$archive_template = $this->locate_archive_template();

		if ( ! $archive_template ) {
			return $template;
		}

		return $archive_template;
	}
}
