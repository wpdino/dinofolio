<?php
/**
 * Elementor integration.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Registers component-backed Elementor widgets.
 */
class Elementor_Integration {

	/**
	 * Singleton instance.
	 *
	 * @var Elementor_Integration|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Elementor_Integration
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
		$this->bootstrap();
	}

	/**
	 * Setup Elementor hooks.
	 *
	 * @return void
	 */
	public function bootstrap() {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		static $hooked = false;

		if ( $hooked ) {
			return;
		}

		$hooked = true;

		require_once DINOFOLIO_PATH . 'includes/integrations/elementor/class-dinofolio-elementor-widget-base.php';

		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		if ( did_action( 'elementor/elements/categories_registered' ) && class_exists( '\Elementor\Plugin' ) ) {
			$this->register_category( \Elementor\Plugin::instance()->elements_manager );
		}

		if ( did_action( 'elementor/widgets/register' ) && class_exists( '\Elementor\Plugin' ) ) {
			$this->register_widgets( \Elementor\Plugin::instance()->widgets_manager );
		}
	}

	/**
	 * Add DinoFolio widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor manager.
	 * @return void
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'dinofolio',
			array(
				'title' => esc_html__( 'WPDINO - DinoFolio', 'dinofolio' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * Register all component widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		foreach ( Components::instance()->all() as $component_name => $component ) {
			$widget_file  = $this->get_widget_file( $component_name );
			$widget_class = $this->get_widget_class_name( $component_name );

			if ( file_exists( $widget_file ) ) {
				require_once $widget_file;

				if ( class_exists( $widget_class ) ) {
					try {
						$widgets_manager->register( new $widget_class() );
					} catch ( \Exception $e ) {
						continue;
					}
					continue;
				}
			}

			try {
				$widgets_manager->register( Elementor_Widget_Base::from_component( $component ) );
			} catch ( \Exception $e ) {
				continue;
			}
		}
	}

	/**
	 * Get widget file path for a component.
	 *
	 * @param string $component_name Component slug.
	 * @return string
	 */
	private function get_widget_file( $component_name ) {
		return DINOFOLIO_PATH . 'includes/integrations/elementor/widgets/' . $component_name . '/widget.php';
	}

	/**
	 * Get widget class name for a component.
	 *
	 * @param string $component_name Component slug.
	 * @return string
	 */
	private function get_widget_class_name( $component_name ) {
		return __NAMESPACE__ . '\\Elementor_' . Util::slug_to_class_suffix( $component_name ) . '_Widget';
	}
}
