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
	 * Go Pro link.
	 *
	 * @var string
	 */
	private static $goProLink = 'https://www.wpdino.com/plugins/dinofolio/?utm_source=wpadmin&utm_medium=dinofolio-free&utm_campaign=go-pro-links';

	/**
	 * The Constructor.
	 */
	public function __construct() {

		// Let's add menu item with subitems
		add_action( 'admin_menu', array( $this, 'register_menus' ), 15 );
		add_action( 'plugin_action_links_' . DINOFOLIO_PLUGIN_BASE, array( $this, 'plugin_action_links' ), 10, 4 );
		
		add_action( 'admin_menu', array( $this, 'plugin_add_go_pro_link_to_menu' ), 15 );

		add_action( 'admin_head', array( $this, 'add_css_go_pro_menu' ) );
		add_action( 'admin_footer', array( $this, 'add_target_blank_go_pro_menu' ) );

	}

	/**
	 * Add settings and go PRO link to plugin page.
	 *
	 * @param array $links Array of links.
	 * @return array
	 */
	public function plugin_action_links( $links, $plugin_file, $plugin_data, $context ) {

		// Add Go Pro link if the plugin is not active
		if( ! defined( 'DINOFOLIO_PRO_VERSION' ) ) {
			$custom['dinofolio-pro'] = sprintf( 
				'<a href="%1$s" aria-label="%2$s" target="_blank" rel="noopener noreferrer" class="wpdino-dinofolio-gopro" style="color:#1A8960;font-weight:bold;">%3$s</a>',
				esc_url( self::$goProLink ),
				esc_attr__( 'Upgrade to PRO', 'dinofolio' ),
				esc_html__( 'Get DinoFolio PRO', 'dinofolio' )
			);
		}

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
	 * Add Go Pro link to the Portfolio menu
	 */
	public function plugin_add_go_pro_link_to_menu() {
		global $submenu;

		// Add Go Pro link to the Portfolio menu
		if( ! defined( 'DINOFOLIO_PRO_VERSION' ) ) {
			$submenu['edit.php?post_type=wpdino_portfolio'][] = array( 
				'' . esc_html__( 'Upgrade to Pro', 'dinofolio' ) . '',
				'manage_options', 
				self::$goProLink 
			);
		}
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

	/**
	 * Add CSS to Go Pro link.
	 */
	public function add_css_go_pro_menu() {
		?>
		<style>
			#adminmenu #menu-posts-wpdino_portfolio a[href="<?php echo self::$goProLink; ?>"] {
				background-color: #1A8960;
				color: #fff;
				font-weight: bold;
			}
		</style>
		<?php
	}

	/**
	 * Add target="_blank" to Go Pro link.
	 */
	public function add_target_blank_go_pro_menu() {
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				$('a[href$="<?php echo self::$goProLink; ?>"]').attr('target', '_blank');				
			});
		</script>
		<?php
	}

}

new Admin_Menus();