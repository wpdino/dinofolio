<?php
/**
 * Legacy settings compatibility.
 *
 * @package DinoFolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * Backward-compatible legacy settings proxy.
 */
if ( ! class_exists( 'WPDINO_Portfolio_Settings' ) ) {
	class WPDINO_Portfolio_Settings {

		/**
		 * Legacy accessor used by old classes.
		 *
		 * @return \DinoFolio\DinoFolio_Settings
		 */
		public static function instance() {
			return \DinoFolio\DinoFolio_Settings::instance();
		}
	}
}

