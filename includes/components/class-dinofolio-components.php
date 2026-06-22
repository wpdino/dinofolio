<?php
/**
 * Components registry.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Loads and stores component instances.
 */
class Components {

	/**
	 * Singleton instance.
	 *
	 * @var Components|null
	 */
	private static $instance = null;

	/**
	 * Loaded component instances.
	 *
	 * @var Component_Base[]
	 */
	private $components = array();

	/**
	 * Singleton accessor.
	 *
	 * @return Components
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
		$this->load_components();
	}

	/**
	 * Return all loaded components keyed by slug.
	 *
	 * @return Component_Base[]
	 */
	public function all() {
		return $this->components;
	}

	/**
	 * Return a component by slug.
	 *
	 * @param string $slug Component slug.
	 * @return Component_Base|null
	 */
	public function get( $slug ) {
		return isset( $this->components[ $slug ] ) ? $this->components[ $slug ] : null;
	}

	/**
	 * Include and instantiate component classes.
	 *
	 * @return void
	 */
	private function load_components() {
		$pattern = DINOFOLIO_PATH . 'includes/components/items/*/class-dinofolio-*-component.php';
		$files   = glob( $pattern );

		if ( empty( $files ) ) {
			return;
		}

		foreach ( $files as $file ) {
			require_once $file;
		}

		$declared_classes = get_declared_classes();

		foreach ( $declared_classes as $class_name ) {
			if ( ! is_subclass_of( $class_name, '\DinoFolio\Component_Base' ) ) {
				continue;
			}

			$instance = new $class_name();
			$slug     = $instance->get_name();

			if ( empty( $slug ) ) {
				continue;
			}

			$this->components[ $slug ] = $instance;
		}

		/**
		 * Filter registered DinoFolio components.
		 *
		 * Pro can register additional component instances.
		 *
		 * @param Component_Base[] $components Loaded components keyed by slug.
		 */
		$this->components = apply_filters( 'dinofolio_register_components', $this->components );

		/**
		 * Fires after DinoFolio components are registered.
		 *
		 * @param Component_Base[] $components Loaded components keyed by slug.
		 */
		do_action( 'dinofolio_components_loaded', $this->components );
	}
}

