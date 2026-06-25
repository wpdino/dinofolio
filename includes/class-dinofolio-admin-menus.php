<?php
/**
 * Register admin menu elements.
 *
 * @since   1.0.0
 * @package DinoFolio
  */

namespace DinoFolio;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for admin menu.
 */
class Admin_Menus {

	/**
	 * The Constructor.
	 */
	public function __construct() {

		// Let's add menu item with subitems
		add_action( 'admin_menu', array( $this, 'register_menus' ), 15 );
		add_action( 'plugin_action_links_' . DINOFOLIO_PLUGIN_BASE, array( $this, 'plugin_action_links' ), 10, 4 );

	}

	/**
	 * Add settings link to plugin page.
	 *
	 * @param array $links Array of links.
	 * @return array
	 */
	public function plugin_action_links( $links, $plugin_file, $plugin_data, $context ) {
		$custom = array();

		// Add settings link to the array
		$custom['dinofolio-settings'] = sprintf(
			'<a href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url( admin_url( 'edit.php?post_type=wpdino_portfolio&page=dinofolio-settings' ) ),
			esc_attr__( 'Go to DinoFolio Settings page', 'dinofolio' ),
			esc_html__( 'Settings', 'dinofolio' )
		);

		$custom['dinofolio-docs'] = sprintf(
			'<a href="%1$s" aria-label="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
			esc_url( 'https://wpdino.com/docs/dinofolio/' ),
			esc_attr__( 'Read the documentation', 'dinofolio' ),
			esc_html__( 'Docs', 'dinofolio' )
		);

		return array_merge( $custom, (array) $links );
		

	}

	/**
	 * Register admin menus.
	 */
	public function register_menus() {
		
		$page_title = esc_html__( 'DinoFolio Settings Page', 'dinofolio' );

		//WPZOOM Portfolio sub menu item.
		add_submenu_page(
			'edit.php?post_type=wpdino_portfolio',
			$page_title,
			esc_html__( 'Settings', 'dinofolio' ),
			'manage_options',
			'dinofolio-settings',
			array( $this, 'admin_page' ),
			5
		);

	}

	/**
	 * Wrapper for the hook to render our custom settings pages.
	 *
	 * @since 1.0.0
	 */
	public function admin_page() {
		do_action( 'wpdino_dinofolio_admin_page' );
	}

}

new Admin_Menus();