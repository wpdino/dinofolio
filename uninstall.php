<?php
/**
 * Uninstall file for DinoFolio Lite.
 *
 * @package DinoFolio
 * @since 1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin settings on uninstall.
delete_option( 'dinofolio_settings' );
