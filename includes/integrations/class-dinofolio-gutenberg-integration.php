<?php
/**
 * Gutenberg integration.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Registers dynamic blocks for all components.
 */
class Gutenberg_Integration {

	/**
	 * Singleton instance.
	 *
	 * @var Gutenberg_Integration|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Gutenberg_Integration
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
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		add_filter( 'block_categories_all', array( $this, 'register_category' ), 10, 2 );
		add_action( 'init', array( $this, 'register_blocks' ), 15 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
	}

	/**
	 * Register DinoFolio block category.
	 *
	 * @param array    $categories Block categories.
	 * @param \WP_Post $post       Current post.
	 * @return array
	 */
	public function register_category( $categories, $post ) {
		unset( $post );

		$exists = false;
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'dinofolio' === $category['slug'] ) {
				$exists = true;
				break;
			}
		}

		if ( ! $exists ) {
			$categories = array_merge(
				array(
					array(
						'slug'  => 'dinofolio',
						'title' => esc_html__( 'DinoFolio', 'dinofolio' ),
						'icon'  => null,
					),
				),
				$categories
			);
		}

		return $categories;
	}

	/**
	 * Register dynamic blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {
		require_once DINOFOLIO_PATH . 'includes/integrations/gutenberg/class-dinofolio-gutenberg-block-base.php';

		foreach ( Components::instance()->all() as $component_name => $component ) {
			$block_file  = $this->get_block_file( $component_name );
			$block_class = $this->get_block_class_name( $component_name );

			if ( file_exists( $block_file ) ) {
				require_once $block_file;

				if ( class_exists( $block_class ) ) {
					$block = new $block_class( $component );
					$block->register();
					continue;
				}
			}

			$block = new Gutenberg_Block_Base( $component );
			$block->register();
		}
	}

	/**
	 * Enqueue shared editor assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		if ( class_exists( 'WPDINO_Portfolio_Display' ) ) {
			\WPDINO_Portfolio_Display::get_instance()->enqueue_listing_assets();
		}

		if ( wp_style_is( 'dinofolio-elementor', 'registered' ) ) {
			wp_enqueue_style( 'dinofolio-elementor' );
		}
	}

	/**
	 * Enqueue listing styles in the block editor canvas iframe.
	 *
	 * @return void
	 */
	public function enqueue_block_assets() {
		if ( ! is_admin() || ! class_exists( 'WPDINO_Portfolio_Display' ) ) {
			return;
		}

		if ( function_exists( 'wp_should_load_block_editor_scripts_and_styles' )
			&& ! wp_should_load_block_editor_scripts_and_styles() ) {
			return;
		}

		\WPDINO_Portfolio_Display::get_instance()->enqueue_listing_assets();
	}

	/**
	 * Get block file path for a component.
	 *
	 * @param string $component_name Component slug.
	 * @return string
	 */
	private function get_block_file( $component_name ) {
		return DINOFOLIO_PATH . 'includes/integrations/gutenberg/blocks/' . $component_name . '/block.php';
	}

	/**
	 * Get block class name for a component.
	 *
	 * @param string $component_name Component slug.
	 * @return string
	 */
	private function get_block_class_name( $component_name ) {
		return __NAMESPACE__ . '\\Gutenberg_' . Util::slug_to_class_suffix( $component_name ) . '_Block';
	}
}
