<?php
/**
 * Portfolio Elementor widget.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Portfolio listing Elementor widget.
 */
class Elementor_Portfolio_Widget extends Elementor_Widget_Base {

	/**
	 * Resolve portfolio component directly.
	 *
	 * @return Component_Base|null
	 */
	protected function get_component() {
		if ( null === $this->component ) {
			$this->component_slug = 'portfolio';
			$this->component      = Components::instance()->get( 'portfolio' );
		}

		return $this->component;
	}
}
