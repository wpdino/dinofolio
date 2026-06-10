<?php
/**
 * DinoFolio Settings Page 
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
 * Class for settings page.
 */
class DinoFolio_Settings {

	/**
	 * Settings option name
	 */
	const OPTION_NAME = 'dinofolio_settings';

	/**
	 * Default settings (generated dynamically from field configuration)
	 */
	private $defaults = null;

	/**
	 * Current settings
	 */
	public static $settings = array();

	/**
	 * Field renderer instance
	 */
	private $field_renderer;

	/**
	 * This class instance.
	 *
	 * @var DinoFolio_Settings
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Provides singleton instance.
	 *
	 * @since 1.0.0
	 * @return self instance
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new DinoFolio_Settings();
		}

		return self::$instance;
	}

	/**
	 * The Constructor.
	 */
	public function __construct() {

		add_action( 'wpdino_dinofolio_admin_page', array( $this, 'settings_page' ) );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'process_form' ) );
		add_action( 'wp_ajax_wpdino_reset_settings', array( $this, 'ajax_reset_settings' ) );
		add_action( 'wp_ajax_wpdino_export_settings', array( $this, 'ajax_export_settings' ) );
		add_action( 'wp_ajax_wpdino_import_settings', array( $this, 'ajax_import_settings' ) );

		// Defer settings loading until init (when translations are safe)
		add_action( 'init', array( $this, 'init_settings' ) );
	}

	/**
	 * Initialize settings at init hook (when translations are safe)
	 */
	public function init_settings() {
		// Reset defaults to null so they get regenerated with translations
		$this->defaults = null;
		
		// Load settings
		$this->load_settings();

		// Initialize field renderer
		$this->field_renderer = new DinoFolio_Field_Renderer( $this );
	}

	/**
	 * Generate default settings from field configuration
	 */
	private function get_defaults() {
		if ( $this->defaults === null ) {
			$this->defaults = array();
			
			// Check if init has run to avoid early translation calls
			if ( ! did_action( 'init' ) ) {
				// Use hardcoded defaults when called too early (before init)
				$this->defaults = $this->get_fallback_defaults();
			} else {
				// Use dynamic defaults from field configuration
				$all_fields = $this->get_all_fields();
				
				foreach ( $all_fields as $field_id => $field ) {
					if ( isset( $field['default'] ) ) {
						$this->defaults[ $field_id ] = $field['default'];
					} else {
						// Set sensible defaults based on field type
						switch ( $field['type'] ) {
							case 'checkbox':
								$this->defaults[ $field_id ] = false;
								break;
							case 'number':
							case 'range':
								$this->defaults[ $field_id ] = 0;
								break;
							case 'multiple_select':
								$this->defaults[ $field_id ] = array();
								break;
							default:
								$this->defaults[ $field_id ] = '';
								break;
						}
					}
				}
			}
		}
		
		return $this->defaults;
	}

	/**
	 * Fallback defaults for early initialization (before init)
	 */
	private function get_fallback_defaults() {
		$defaults = array();
		$sections = $this->get_settings_sections();
		
		foreach ( $sections as $section ) {
			if ( ! empty( $section['fields'] ) ) {
				foreach ( $section['fields'] as $field ) {
					// Skip subsection fields (they don't have values)
					if ( isset( $field['type'] ) && $field['type'] === 'subsection' ) {
						continue;
					}
					
					// Handle row type (nested fields)
					if ( isset( $field['type'] ) && $field['type'] === 'row' && ! empty( $field['fields'] ) ) {
						foreach ( $field['fields'] as $row_field ) {
							if ( isset( $row_field['id'] ) && isset( $row_field['default'] ) ) {
								$defaults[ $row_field['id'] ] = $row_field['default'];
							}
						}
					} elseif ( isset( $field['id'] ) && isset( $field['default'] ) ) {
						$defaults[ $field['id'] ] = $field['default'];
					}
				}
			}
		}
		
		return $defaults;
	}

	/**
	 * Load settings from database
	 */
	private function load_settings() {
		$saved_settings = get_option( self::OPTION_NAME, array() );
		self::$settings = wp_parse_args( $saved_settings, $this->get_defaults() );
	}

	/**
	 * Get setting value
	 */
	public function get_setting( $key, $default = null ) {
		// Ensure settings are loaded
		if ( empty( self::$settings ) ) {
			$this->load_settings();
		}

		if ( isset( self::$settings[ $key ] ) && '' !== self::$settings[ $key ] && null !== self::$settings[ $key ] ) {
			return self::$settings[ $key ];
		}

		$legacy_settings_keys = array(
			'portfolio_meta_default_related_projects_style'  => 'portfolio_meta_default_related_works',
			'portfolio_meta_default_related_projects_title'  => 'portfolio_meta_default_related_works_title',
			'portfolio_meta_default_related_projects_number' => 'portfolio_meta_default_related_works_number',
		);

		if ( isset( $legacy_settings_keys[ $key ] ) && isset( self::$settings[ $legacy_settings_keys[ $key ] ] ) ) {
			$legacy_value = self::$settings[ $legacy_settings_keys[ $key ] ];
			if ( '' !== $legacy_value && null !== $legacy_value ) {
				return $legacy_value;
			}
		}

		$defaults = $this->get_defaults();
		return $default !== null ? $default : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : null );
	}

	/**
	 * Update setting value
	 */
	public function update_setting( $key, $value ) {
		self::$settings[ $key ] = $value;
		return update_option( self::OPTION_NAME, self::$settings );
	}

	/**
	 * Get all settings
	 */
	public static function get_all_settings() {
		// Ensure settings are loaded if instance exists
		$instance = self::instance();
		if ( empty( self::$settings ) ) {
			$instance->load_settings();
		}
		return self::$settings;
	}

	/**
	 * Update all settings
	 */
	public function update_all_settings( $new_settings ) {
		// Get defaults to ensure all fields are included
		$defaults = $this->get_defaults();
		
		// Merge new settings with defaults, but ensure new_settings values take precedence
		// This ensures unchecked checkboxes (false) are saved correctly
		self::$settings = array_merge( $defaults, $new_settings );
		
		return update_option( self::OPTION_NAME, self::$settings );
	}

	/**
	 * Process form submission (dynamic version based on field configuration)
	 */
	public function process_form() {
		if ( ! isset( $_POST['wpdino_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpdino_settings_nonce'] ) ), 'wpdino_settings_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get the settings array from POST using filter_input for better security
		$raw_post_settings = filter_input( INPUT_POST, self::OPTION_NAME, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( $raw_post_settings === null ) {
			$raw_post_settings = array();
		}
		$post_settings = array();
		foreach ( $raw_post_settings as $key => $value ) {
			$post_settings[ sanitize_key( $key ) ] = sanitize_text_field( $value );
		}
		$updated_settings = array();

		// Get all field configurations
		$all_fields = $this->get_all_fields();

		// Process each field dynamically
		foreach ( $all_fields as $field_id => $field ) {
			// Skip subsection fields (they don't have values)
			if ( isset( $field['type'] ) && $field['type'] === 'subsection' ) {
				continue;
			}
			
			// Skip PRO widgets (they're promo/info only)
			if ( isset( $field['is_pro'] ) && $field['is_pro'] ) {
				continue;
			}
			
			$field_name = isset( $field['name'] ) ? $field['name'] : $field_id;
			$default_value = isset( $field['default'] ) ? $field['default'] : '';
			
			// Get the posted value for this field
			$posted_value = null;
			if ( $field['type'] === 'checkbox' ) {
				// For checkboxes, check if the field name exists in POST data
				$posted_value = isset( $post_settings[ $field_name ] );
			} else {
				// For other fields, get the actual value
				$posted_value = $post_settings[ $field_name ] ?? $default_value;
			}

			// Sanitize the value based on field type
			$updated_settings[ $field_id ] = $this->sanitize_field_value( $field, $posted_value );
		}

		// Update settings
		if ( $this->update_all_settings( $updated_settings ) ) {
			// Redirect to same page with success parameter
			wp_safe_redirect( add_query_arg( 'settings-updated', 'true', wp_get_referer() ) );
			exit;
		}
	}

	/**
	 * Add UTM tracking parameters to URL
	 *
	 * @param string $url The original URL
	 * @param string $content The content identifier for utm_content
	 * @return string URL with UTM parameters
	 */
	private function add_utm_params( $url, $content = '' ) {
		$utm_params = array(
			'utm_source' => 'plugin',
			'utm_medium' => 'settings_page',
			'utm_campaign' => 'dinofolio_free',
		);
		
		if ( ! empty( $content ) ) {
			$utm_params['utm_content'] = $content;
		}
		
		$separator = strpos( $url, '?' ) !== false ? '&' : '?';
		return $url . $separator . http_build_query( $utm_params );
	}

	/**
	 * Render PRO upsell banner
	 */
	private function render_pro_upsell() {
		// Check if PRO version is already installed
		if ( class_exists( 'DinoFolioPro\Plugin' ) ) {
			return; // Don't show upsell if PRO is installed
		}
		?>
		<div class="wpdino-card wpdino-pro-card">
			<div class="wpdino-pro-header">
				<div class="wpdino-pro-badge">
					<?php esc_html_e( 'PRO', 'dinofolio' ); ?>
				</div>
				<h2><?php esc_html_e( 'Upgrade to DinoFolio PRO', 'dinofolio' ); ?></h2>
				<p><?php esc_html_e( 'Unlock advanced AI-powered widgets and premium features to take your Elementor designs to the next level.', 'dinofolio' ); ?></p>
				
				<div class="wpdino-pro-features">
					<ul>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'AI Content Generator', 'dinofolio' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'AI Content Summarizer', 'dinofolio' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'AI FAQ Generator', 'dinofolio' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'AI SEO Optimizer', 'dinofolio' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'AI Social Media Post Generator', 'dinofolio' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'AI Image Generator', 'dinofolio' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Premium Widgets Collection', 'dinofolio' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Priority Support', 'dinofolio' ); ?>
						</li>
					</ul>
				</div>
				
				<div class="wpdino-pro-cta">
					<a href="<?php echo esc_url( $this->add_utm_params( 'https://wpdino.com/plugins/dinofolio-pro-for-elementor/', 'pro_upgrade_button' ) ); ?>" target="_blank" class="wpdino-btn wpdino-btn-primary">
						<?php esc_html_e( 'Get DinoFolio PRO', 'dinofolio' ); ?>
						<span class="dashicons dashicons-arrow-right-alt"></span>
					</a>
					<p>
						<?php esc_html_e( '30-day money-back guarantee', 'dinofolio' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render admin notices
	 */
	private function render_admin_notices() {
		// Check for settings updated
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking for display flag
		if ( isset( $_GET['settings-updated'] ) && sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) === 'true' ) {
			?>
			<div class="wpdino-admin-notice wpdino-admin-notice-success">
				<span class="dashicons dashicons-yes-alt"></span>
				<span><?php esc_html_e( 'Settings saved successfully!', 'dinofolio' ); ?></span>
				<button type="button" class="wpdino-notice-dismiss">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<?php
		}
		
		// Check for settings reset
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking for display flag
		if ( isset( $_GET['settings-reset'] ) && sanitize_text_field( wp_unslash( $_GET['settings-reset'] ) ) === 'true' ) {
			?>
			<div class="wpdino-admin-notice wpdino-admin-notice-reset">
				<span class="dashicons dashicons-backup"></span>
				<span><?php esc_html_e( 'Settings have been reset to defaults.', 'dinofolio' ); ?></span>
				<button type="button" class="wpdino-notice-dismiss">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<?php
		}
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_scripts( $hook ) {

		// Only load on the settings page
		$valid_hooks = array(
			'wpdino_portfolio_page_dinofolio-settings',
			'dinofolio_page_dinofolio-settings'
		);
		
		// Check if we're on the settings page
		$is_settings_page = in_array( $hook, $valid_hooks );
		
		// Also check by GET parameter as fallback.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen routing.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( ! $is_settings_page && 'dinofolio-settings' === $page ) {
			$is_settings_page = true;
		}
		
		if ( ! $is_settings_page ) {
			return;
		}

		// Enqueue color picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		
		// Enqueue media uploader
		wp_enqueue_media();

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'wpdino-admin',
			DINOFOLIO_URL . 'includes/admin/assets/css/admin.css',
			array( 'dashicons', 'wp-color-picker' ),
			DINOFOLIO_VERSION
		);
		wp_enqueue_style(
			'wpdino-portfolio-meta-admin',
			DINOFOLIO_URL . 'includes/admin/assets/css/admin-portfolio-meta.css',
			array( 'wpdino-admin' ),
			DINOFOLIO_VERSION
		);

		wp_enqueue_script(
			'wpdino-admin',
			DINOFOLIO_URL . 'includes/admin/assets/js/admin.js',
			array( 'jquery', 'wp-color-picker', 'jquery-ui-datepicker', 'media-upload', 'thickbox' ),
			DINOFOLIO_VERSION,
			true
		);

		wp_localize_script( 'wpdino-admin', 'wpdinoAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wpdino_admin_action' ),
			'strings' => array(
				'confirmReset'  => esc_html__( 'Are you sure you want to reset all settings to defaults? This action cannot be undone.', 'dinofolio' ),
				'confirmImport' => esc_html__( 'Are you sure you want to import these settings? This will overwrite your current settings.', 'dinofolio' ),
				'resetSuccess'  => esc_html__( 'Settings have been reset to defaults.', 'dinofolio' ),
				'exportSuccess' => esc_html__( 'Settings exported successfully!', 'dinofolio' ),
				'importSuccess' => esc_html__( 'Settings imported successfully!', 'dinofolio' ),
				'copySuccess'   => esc_html__( 'System info copied to clipboard!', 'dinofolio' ),
				'copyError'     => esc_html__( 'Failed to copy. Please select and copy manually.', 'dinofolio' ),
				'error'         => esc_html__( 'An error occurred. Please try again.', 'dinofolio' ),
				'invalidFile'   => esc_html__( 'Please select a valid JSON file.', 'dinofolio' ),
				'unsavedChanges' => esc_html__( 'You have unsaved changes. Please save your changes before leaving this page.', 'dinofolio' ),
			)
		) );
	}

	/**
	 * AJAX Reset Settings
	 */
	public function ajax_reset_settings() {
		check_ajax_referer( 'wpdino_admin_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'dinofolio' ) );
		}

		$this->update_all_settings( $this->get_defaults() );

		wp_send_json_success( array(
			'message' => esc_html__( 'Settings have been reset to defaults.', 'dinofolio' ),
			'redirect' => add_query_arg( 'settings-reset', 'true', admin_url( 'admin.php?page=dinofolio-settings' ) )
		) );
	}

	/**
	 * AJAX Export Settings
	 */
	public function ajax_export_settings() {
		check_ajax_referer( 'wpdino_admin_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'dinofolio' ) );
		}

		wp_send_json_success( array(
			'settings' => self::get_all_settings(),
			'filename' => 'dinofolio-settings-' . gmdate( 'Y-m-d-H-i-s' ) . '.json'
		) );
	}

	/**
	 * AJAX Import Settings
	 */
	public function ajax_import_settings() {
		check_ajax_referer( 'wpdino_admin_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'dinofolio' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized on the next line
		$settings_json = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '';
		$settings_json = sanitize_textarea_field( $settings_json );
		$settings = json_decode( $settings_json, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Invalid JSON format.', 'dinofolio' )
			) );
		}

		$this->update_all_settings( $settings );

		wp_send_json_success( array(
			'message' => esc_html__( 'Settings imported successfully!', 'dinofolio' )
		) );
	}

	/**
	 * Get all fields from all sections (flattened)
	 */
	private function get_all_fields() {
		$all_fields = array();
		$sections = $this->get_settings_sections();
		
		foreach ( $sections as $section ) {
			if ( ! empty( $section['fields'] ) ) {
				foreach ( $section['fields'] as $field ) {
					// Skip subsection fields (they don't have values)
					if ( isset( $field['type'] ) && $field['type'] === 'subsection' ) {
						continue;
					}
					
					// Handle row type (nested fields)
					if ( isset( $field['type'] ) && $field['type'] === 'row' && ! empty( $field['fields'] ) ) {
						foreach ( $field['fields'] as $row_field ) {
							if ( isset( $row_field['id'] ) ) {
								$all_fields[ $row_field['id'] ] = $row_field;
							}
						}
					} elseif ( isset( $field['id'] ) ) {
						$all_fields[ $field['id'] ] = $field;
					}
				}
			}
		}
		
		return $all_fields;
	}

	/**
	 * Sanitize and validate field value based on its type
	 */
	private function sanitize_field_value( $field, $value ) {
		$default = isset( $field['default'] ) ? $field['default'] : '';
		
		// First, unslash the value if it's slashed
		$value = wp_unslash( $value );
		
		// Validate that the value is not null or empty string (unless it's a checkbox)
		if ( $field['type'] !== 'checkbox' && ( $value === null || $value === '' ) ) {
			return $default;
		}
		
		switch ( $field['type'] ) {
			case 'text':
				$sanitized = sanitize_text_field( $value );
				// Validate length if specified
				if ( isset( $field['maxlength'] ) && strlen( $sanitized ) > $field['maxlength'] ) {
					$sanitized = substr( $sanitized, 0, $field['maxlength'] );
				}
				return $sanitized;
				
			case 'textarea':
				$sanitized = sanitize_textarea_field( $value );
				// Validate length if specified
				if ( isset( $field['maxlength'] ) && strlen( $sanitized ) > $field['maxlength'] ) {
					$sanitized = substr( $sanitized, 0, $field['maxlength'] );
				}
				return $sanitized;
				
			case 'email':
				$sanitized = sanitize_email( $value );
				// Validate email format
				if ( ! is_email( $sanitized ) ) {
					return $default;
				}
				return $sanitized;
				
			case 'url':
				$sanitized = esc_url_raw( $value );
				// Validate URL format
				if ( ! filter_var( $sanitized, FILTER_VALIDATE_URL ) ) {
					return $default;
				}
				return $sanitized;
				
			case 'number':
			case 'range':
				// Validate that value is numeric
				if ( ! is_numeric( $value ) ) {
					return $default;
				}
				
				$num_value = floatval( $value );
				
				// Apply min constraint
				if ( isset( $field['min'] ) && $num_value < $field['min'] ) {
					$num_value = $field['min'];
				}
				
				// Apply max constraint  
				if ( isset( $field['max'] ) && $num_value > $field['max'] ) {
					$num_value = $field['max'];
				}
				
				// For range and integer number fields, return as integer
				if ( $field['type'] === 'range' || ( isset( $field['step'] ) && $field['step'] == 1 ) ) {
					return intval( $num_value );
				}
				
				return $num_value;
				
			case 'checkbox':
				// Validate checkbox value (should be boolean or string)
				return ! empty( $value ) && $value !== 'false' && $value !== '0';
				
			case 'select':
			case 'radio':
			case 'toggle_radio':
			case 'image_select':
				// Validate against available options
				if ( isset( $field['options'] ) && array_key_exists( $value, $field['options'] ) ) {
					return sanitize_text_field( $value );
				}
				return $default;
				
			case 'multiple_select':
				if ( is_array( $value ) ) {
					$sanitized = array();
					foreach ( $value as $item ) {
						$item = wp_unslash( $item );
						if ( isset( $field['options'] ) && array_key_exists( $item, $field['options'] ) ) {
							$sanitized[] = sanitize_text_field( $item );
						}
					}
					return $sanitized;
				}
				return $default;
				
			case 'colorpicker':
				$sanitized = sanitize_hex_color( $value );
				// Validate hex color format
				if ( ! $sanitized ) {
					return $default;
				}
				return $sanitized;
				
			case 'editor':
				// Use wp_kses_post for rich text content
				return wp_kses_post( $value );
				
			case 'file':
				// Validate image URL and check if it's a valid attachment
				$url = esc_url_raw( $value );
				if ( empty( $url ) ) {
					return '';
				}
				
				// Validate URL format
				if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
					return $default;
				}
				
				// Check if it's a valid WordPress attachment URL
				$attachment_id = attachment_url_to_postid( $url );
				if ( $attachment_id ) {
					// Verify it's an image attachment
					if ( wp_attachment_is_image( $attachment_id ) ) {
						return $url;
					}
				} elseif ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
					// For external URLs, check if it has an image extension
					$path_info = pathinfo( wp_parse_url( $url, PHP_URL_PATH ) );
					$image_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp' );
					
					if ( isset( $path_info['extension'] ) && 
						 in_array( strtolower( $path_info['extension'] ), $image_extensions ) ) {
						return $url;
					}
				}
				
				return $default;
				
			default:
				// For custom field types, apply basic text sanitization
				if ( is_array( $value ) ) {
					return array_map( 'sanitize_text_field', array_map( 'wp_unslash', $value ) );
				}
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Get available widgets list
	 *
	 * @since 1.0.2
	 * @return array Array of widget slugs and their display names
	 */
	private function get_available_widgets() {
		$widgets = array();
		$widgets_dir = DINOFOLIO_PATH . 'includes/elementor/widgets/';
		
		// Widget name mapping for better display names
		$widget_names = array(
			'dinofolio-portfolio' => esc_html__( 'DinoFolio Portfolio', 'dinofolio' ),
		);
		
		if ( ! is_dir( $widgets_dir ) ) {
			return $widgets;
		}
		
		foreach ( glob( $widgets_dir . '*', GLOB_ONLYDIR | GLOB_NOSORT ) as $path ) {
			$slug = basename( $path );
			$file = trailingslashit( $path ) . $slug . '.php';
			
			if ( file_exists( $file ) ) {
				// Use mapped name if available, otherwise convert slug to readable name
				if ( isset( $widget_names[ $slug ] ) ) {
					$name = $widget_names[ $slug ];
				} else {
					$name = str_replace( '-', ' ', $slug );
					$name = ucwords( $name );
				}
				$widgets[ $slug ] = $name;
			}
		}
		
		// Sort widgets alphabetically by name
		asort( $widgets );
		
		/**
		 * Filter available widgets to allow extensions (like PRO) to add their own widgets.
		 *
		 * @since 1.0.0
		 * @param array $widgets Array of widget slugs => widget names.
		 */
		return apply_filters( 'dinofolio_available_widgets', $widgets );
	}

	/**
	 * Get PRO widgets list with names and descriptions
	 *
	 * @since 1.0.2
	 * @return array Array of widget slugs => array( 'name' => string, 'description' => string )
	 */
	private function get_pro_widgets() {
		// If PRO version is already installed, don't show the promo list at all
		if ( class_exists( 'DinoFolioPro\Plugin' ) ) {
			return array();
		}
		
		// PRO widget name and description mapping
		$pro_widgets_info = array(
		);
		
		// We always return the full PRO widget list in the free version,
		// regardless of whether the PRO plugin files are present.
		// This is purely for promo/info purposes.
		$widgets = $pro_widgets_info;

		// Sort widgets alphabetically by name
		uasort( $widgets, function( $a, $b ) {
			return strcmp( $a['name'], $b['name'] );
		} );

		return $widgets;
	}

	/**
	 * Settings sections configuration
	 */
	public function get_settings_sections() {
		$available_widgets = $this->get_available_widgets();
		$widget_fields = array();
		
		// Add widget checkboxes
		foreach ( $available_widgets as $widget_slug => $widget_name ) {
			$widget_fields[] = array(
				'type' => 'checkbox',
				'id'   => 'widget_enable_' . $widget_slug,
				'name' => 'widget_enable_' . $widget_slug,
				'label' => $widget_name,
				'description' => '',
				'default' => true, // All widgets enabled by default
			);
		}
		
		// Get PRO widgets (only if PRO is not activated)
		$pro_widgets = $this->get_pro_widgets();
		$pro_widget_fields = array();
		
		// Add PRO widget checkboxes (promo/info only, disabled but checked)
		if ( ! empty( $pro_widgets ) ) {
			foreach ( $pro_widgets as $widget_slug => $widget_info ) {
				$pro_widget_fields[] = array(
					'type' => 'checkbox',
					'id'   => 'widget_pro_' . $widget_slug,
					'name' => 'widget_pro_' . $widget_slug,
					'label' => $widget_info['name'],
					'description' => ! empty( $widget_info['description'] ) ? $widget_info['description'] : '',
					'default' => true, // Show as checked by default (available in PRO)
					'disabled' => true, // Disabled (non-interactive)
					'is_pro' => true, // Mark as PRO widget (excluded from form processing)
				);
			}
		}

		$wp_admin_permalink_url = admin_url('options-permalink.php');
		
		// Build fields array
		$general_fields = array(
			array(
				'type' => 'text',
				'id'   => 'portfolio_slug',
				'name' => 'portfolio_slug',
				'label' => esc_html__( 'Portfolio Slug', 'dinofolio' ),
				'description' => sprintf(
					/* translators: %s: Permalinks settings URL in the WordPress admin. */
					wp_kses_post( __( 'Define a custom portfolio slug. After saving, refresh permalinks in <a href="%s" target="_blank">WordPress Settings > Permalinks</a> to apply changes', 'dinofolio' ) ),
					esc_url( $wp_admin_permalink_url )
				),
				'placeholder' => esc_html__( 'Enter your portfolio slug', 'dinofolio' ),
				'default' => 'dinofolio-portfolio',
			),
			array(
				'type' => 'text',
				'id'   => 'portfolio_tax_slug',
				'name' => 'portfolio_tax_slug',
				'label' => esc_html__( 'Portfolio Taxonomy Slug', 'dinofolio' ),
				'description' => sprintf(
					/* translators: %s: Permalinks settings URL in the WordPress admin. */
					wp_kses_post( __( 'Define a custom portfolio taxonomy slug. After saving, refresh permalinks in <a href="%s" target="_blank">WordPress Settings > Permalinks</a> to apply changes', 'dinofolio' ) ),
					esc_url( $wp_admin_permalink_url )
				),
				'placeholder' => esc_html__( 'Enter your portfolio taxonomy slug', 'dinofolio' ),
				'default' => 'dinofolio-portfolio-category', 
			),
		);

		$general_fields[] = array(
			'type' => 'subsection',
			'id'   => 'default_settings_subsection', 
			'name' => 'default_settings_subsection',
			'label' => esc_html__( 'Default Portfolio Settings', 'dinofolio' ),
			'description' => esc_html__( 'Configure default settings for portfolio item in metaboxes.', 'dinofolio' ),
		);

		$default_settings_fields = array(
			array(
				'type' => 'toggle_radio',
				'id'   => 'portfolio_meta_default_featured_image_display',
				'name' => 'portfolio_meta_default_featured_image_display',
				'label' => esc_html__( 'Featured Image', 'dinofolio' ),
				'description' => esc_html__( 'Default featured image visibility for new portfolio items.', 'dinofolio' ),
				'options' => array(
					'on'  => esc_html__( 'On', 'dinofolio' ),
					'off' => esc_html__( 'Off', 'dinofolio' ),
				),
				'default' => 'on',
			),
			array(
				'type' => 'select',
				'id'   => 'portfolio_meta_default_featured_image_size',
				'name' => 'portfolio_meta_default_featured_image_size',
				'label' => esc_html__( 'Featured Image Size', 'dinofolio' ),
				'description' => esc_html__( 'Default featured image size when enabled.', 'dinofolio' ),
				'options' => $this->get_ordered_image_sizes(),
				'default' => 'dinofolio-featured-1200x900',
				'class' => 'wpdino-featured-image-size-setting',
			),
			array(
				'type' => 'toggle_radio',
				'id'   => 'portfolio_meta_default_related_projects',
				'name' => 'portfolio_meta_default_related_projects',
				'label' => esc_html__( 'Related Projects', 'dinofolio' ),
				'description' => esc_html__( 'Default related projects visibility for new portfolio items.', 'dinofolio' ),
				'options' => array(
					'on'  => esc_html__( 'On', 'dinofolio' ),
					'off' => esc_html__( 'Off', 'dinofolio' ),
				),
				'default' => 'on',
			),
			array(
				'type' => 'image_select',
				'id'   => 'portfolio_meta_default_related_projects_style',
				'name' => 'portfolio_meta_default_related_projects_style',
				'label' => esc_html__( 'Related Projects Style', 'dinofolio' ),
				'description' => esc_html__( 'Default related projects style for portfolio items.', 'dinofolio' ),
				'options' => $this->get_related_projects_style_options(),
				'default' => 'grid',
			),
			array(
				'type' => 'text',
				'id'   => 'portfolio_meta_default_related_projects_title',
				'name' => 'portfolio_meta_default_related_projects_title',
				'label' => esc_html__( 'Related Projects Title', 'dinofolio' ),
				'description' => esc_html__( 'Default related projects heading on single portfolio pages.', 'dinofolio' ),
				'default' => esc_html__( 'Related Projects', 'dinofolio' ),
			),
			array(
				'type' => 'range',
				'id'   => 'portfolio_meta_default_related_projects_number',
				'name' => 'portfolio_meta_default_related_projects_number',
				'label' => esc_html__( 'Number of Related Projects', 'dinofolio' ),
				'description' => esc_html__( 'Grid: number of related projects to show. Carousel: how many projects are visible at once (all related projects are loaded and scrollable).', 'dinofolio' ),
				'min'  => 2,
				'max'  => 5,
				'step' => 1,
				'default' => 3,
			),
			array(
				'type' => 'toggle_radio',
				'id'   => 'portfolio_meta_default_date_display',
				'name' => 'portfolio_meta_default_date_display',
				'label' => esc_html__( 'Date Display', 'dinofolio' ),
				'description' => esc_html__( 'Default date visibility for portfolio items.', 'dinofolio' ),
				'options' => array(
					'on'  => esc_html__( 'On', 'dinofolio' ),
					'off' => esc_html__( 'Off', 'dinofolio' ),
				),
				'default' => 'on',
			),
			array(
				'type' => 'text',
				'id'   => 'portfolio_meta_default_date_label',
				'name' => 'portfolio_meta_default_date_label',
				'label' => esc_html__( 'Date Label', 'dinofolio' ),
				'description' => esc_html__( 'Default label used before the date value.', 'dinofolio' ),
				'default' => esc_html__( 'Date', 'dinofolio' ),
			),
			array(
				'type' => 'text',
				'id'   => 'portfolio_meta_default_date_of_work',
				'name' => 'portfolio_meta_default_date_of_work',
				'label' => esc_html__( 'Date of Work', 'dinofolio' ),
				'description' => esc_html__( 'Default date value when not set per portfolio item.', 'dinofolio' ),
				'class' => 'wpdino-date-picker',
				'default' => '',
			),
			array(
				'type' => 'url',
				'id'   => 'portfolio_meta_default_external_url',
				'name' => 'portfolio_meta_default_external_url',
				'label' => esc_html__( 'External URL', 'dinofolio' ),
				'description' => esc_html__( 'Default external URL for portfolio items.', 'dinofolio' ),
				'default' => '',
			),
			array(
				'type' => 'text',
				'id'   => 'portfolio_meta_default_button_label',
				'name' => 'portfolio_meta_default_button_label',
				'label' => esc_html__( 'Button Label', 'dinofolio' ),
				'description' => esc_html__( 'Default CTA button label.', 'dinofolio' ),
				'default' => esc_html__( 'Launch', 'dinofolio' ),
			),
			array(
				'type' => 'textarea',
				'id'   => 'portfolio_meta_default_attributes',
				'name' => 'portfolio_meta_default_attributes',
				'label' => esc_html__( 'Default Attributes', 'dinofolio' ),
				'description' => esc_html__( 'One attribute per line in this format: Label|Value', 'dinofolio' ),
				'rows' => 4,
				'default' => '',
			),
		);

		$general_fields = array_merge( $general_fields, $default_settings_fields );
		
		// Add widgets subsection
		if ( ! empty( $widget_fields ) ) {
			$general_fields[] = array(
				'type' => 'subsection',
				'id'   => 'widgets_subsection',
				'name' => 'widgets_subsection',
				'label' => esc_html__( 'Widgets', 'dinofolio' ),
				'description' => esc_html__( 'Enable or disable widgets to show in the Elementor panel. All widgets are enabled by default.', 'dinofolio' ),
			);
			$general_fields = array_merge( $general_fields, $widget_fields );
			
			// Add PRO widgets subsection if PRO widgets exist
			if ( ! empty( $pro_widget_fields ) ) {
				$general_fields[] = array(
					'type' => 'subsection',
					'id'   => 'pro_widgets_subsection',
					'name' => 'pro_widgets_subsection',
					'label' => esc_html__( 'PRO Widgets', 'dinofolio' ),
					'description' => sprintf(
						/* translators: %s: DinoFolio PRO plugin URL. */
						wp_kses_post( __( 'These widgets are available in <a href="%s" target="_blank">DinoFolio PRO</a>. Upgrade to unlock these powerful features.', 'dinofolio' ) ),
						esc_url( $this->add_utm_params( 'https://wpdino.com/plugins/dinofolio/', 'pro_widgets_section' ) )
					),
				);
				$general_fields = array_merge( $general_fields, $pro_widget_fields );
			}
		}
		
		$sections = array(
			'general' => array(
				'id'          => 'general',
				'title'       => esc_html__( 'General', 'dinofolio' ),
				'description' => esc_html__( 'Configure basic DinoFolio settings.', 'dinofolio' ),
				'callback'    => null,
				'icon'        => 'dashicons-admin-generic',
				'fields'      => $general_fields,
			),
			'taxonomy' => array(
				'id'          => 'taxonomy',
				'title'       => esc_html__( 'Taxonomy Archive', 'dinofolio' ),
				'description' => esc_html__( 'Configure the portfolio category and tag archive template using the same listing options as the block and Elementor widget.', 'dinofolio' ),
				'callback'    => null,
				'icon'        => 'dashicons-category',
				'fields'      => $this->get_taxonomy_settings_fields(),
			),
			'tools' => array(
				'id' => 'tools',
				'title' => esc_html__( 'Tools', 'dinofolio' ),
				'description' => esc_html__( 'Import, export, and manage your DinoFolio settings with powerful backup and restore tools.', 'dinofolio' ),
				'callback' => array( $this, 'render_tools_section' ),
				'icon' => 'dashicons-admin-settings',
				'fields' => array(),
			),
		);

		/**
		 * Filter settings sections to allow extensions (like PRO) to add their own sections.
		 *
		 * @since 1.0.0
		 * @param array $sections Settings sections array.
		 */
		return apply_filters( 'dinofolio_settings_sections', $sections );
	}

	/**
	 * Shared portfolio listing style fields.
	 *
	 * @param string $prefix Setting key prefix, e.g. taxonomy_.
	 * @return array
	 */
	private function get_listing_style_setting_fields( $prefix = '' ) {
		return array(
			array(
				'type'    => 'select',
				'id'      => $prefix . 'style',
				'name'    => $prefix . 'style',
				'label'   => esc_html__( 'Card Style', 'dinofolio' ),
				'options' => array(
					'classic' => esc_html__( 'Classic', 'dinofolio' ),
					'overlay' => esc_html__( 'Overlay', 'dinofolio' ),
				),
				'default' => 'classic',
			),
			array(
				'type'    => 'select',
				'id'      => $prefix . 'hover_effect',
				'name'    => $prefix . 'hover_effect',
				'label'   => esc_html__( 'Hover Effect', 'dinofolio' ),
				'options' => array(
					'zoom' => esc_html__( 'Zoom', 'dinofolio' ),
				),
				'default' => 'zoom',
			),
			array(
				'type'    => 'number',
				'id'      => $prefix . 'gap',
				'name'    => $prefix . 'gap',
				'label'   => esc_html__( 'Columns Gap', 'dinofolio' ),
				'min'     => 0,
				'max'     => 80,
				'default' => 24,
			),
			array(
				'type'    => 'number',
				'id'      => $prefix . 'radius',
				'name'    => $prefix . 'radius',
				'label'   => esc_html__( 'Border Radius', 'dinofolio' ),
				'min'     => 0,
				'max'     => 40,
				'default' => 8,
			),
			array(
				'type'    => 'colorpicker',
				'id'      => $prefix . 'accent_color',
				'name'    => $prefix . 'accent_color',
				'label'   => esc_html__( 'Accent Color', 'dinofolio' ),
				'default' => '#1a8960',
			),
			array(
				'type'    => 'colorpicker',
				'id'      => $prefix . 'hover_color',
				'name'    => $prefix . 'hover_color',
				'label'   => esc_html__( 'Button Hover Color', 'dinofolio' ),
				'default' => '#1a8970',
			),
			array(
				'type'    => 'colorpicker',
				'id'      => $prefix . 'button_text_color',
				'name'    => $prefix . 'button_text_color',
				'label'   => esc_html__( 'Button Text Color', 'dinofolio' ),
				'default' => '#ffffff',
			),
			array(
				'type'    => 'colorpicker',
				'id'      => $prefix . 'muted_color',
				'name'    => $prefix . 'muted_color',
				'label'   => esc_html__( 'Muted Text Color', 'dinofolio' ),
				'default' => '#666666',
			),
		);
	}

	/**
	 * Taxonomy archive template settings fields.
	 *
	 * @return array
	 */
	private function get_taxonomy_settings_fields() {
		$fields = array(
			array(
				'type'        => 'checkbox',
				'id'          => 'taxonomy_use_template',
				'name'        => 'taxonomy_use_template',
				'label'       => esc_html__( 'Use Plugin Taxonomy Template', 'dinofolio' ),
				'description' => esc_html__( 'Replace the theme taxonomy archive with the DinoFolio listing template for portfolio categories and tags.', 'dinofolio' ),
				'default'     => true,
			),
			array(
				'type'        => 'subsection',
				'id'          => 'taxonomy_display_subsection',
				'name'        => 'taxonomy_display_subsection',
				'label'       => esc_html__( 'Display', 'dinofolio' ),
				'description' => esc_html__( 'Same options as the portfolio listing block and Elementor widget.', 'dinofolio' ),
			),
			array(
				'type'    => 'select',
				'id'      => 'taxonomy_layout',
				'name'    => 'taxonomy_layout',
				'label'   => esc_html__( 'Layout', 'dinofolio' ),
				'options' => array(
					'grid'    => esc_html__( 'Grid', 'dinofolio' ),
					'masonry' => esc_html__( 'Masonry', 'dinofolio' ),
					'list'    => esc_html__( 'List', 'dinofolio' ),
				),
				'default' => 'grid',
			),
			array(
				'type'    => 'select',
				'id'      => 'taxonomy_columns',
				'name'    => 'taxonomy_columns',
				'label'   => esc_html__( 'Columns', 'dinofolio' ),
				'options' => array(
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'default' => '3',
			),
			array(
				'type'    => 'select',
				'id'      => 'taxonomy_image_size',
				'name'    => 'taxonomy_image_size',
				'label'   => esc_html__( 'Image Size', 'dinofolio' ),
				'options' => $this->get_ordered_image_sizes(),
				'default' => 'large',
			),
			array(
				'type'    => 'checkbox',
				'id'      => 'taxonomy_show_title',
				'name'    => 'taxonomy_show_title',
				'label'   => esc_html__( 'Show Title', 'dinofolio' ),
				'default' => true,
			),
			array(
				'type'    => 'checkbox',
				'id'      => 'taxonomy_show_categories',
				'name'    => 'taxonomy_show_categories',
				'label'   => esc_html__( 'Show Categories', 'dinofolio' ),
				'default' => true,
			),
			array(
				'type'    => 'checkbox',
				'id'      => 'taxonomy_show_excerpt',
				'name'    => 'taxonomy_show_excerpt',
				'label'   => esc_html__( 'Show Excerpt', 'dinofolio' ),
				'default' => true,
			),
			array(
				'type'    => 'checkbox',
				'id'      => 'taxonomy_show_read_more',
				'name'    => 'taxonomy_show_read_more',
				'label'   => esc_html__( 'Show Read More Button', 'dinofolio' ),
				'default' => true,
			),
			array(
				'type'    => 'text',
				'id'      => 'taxonomy_read_more_label',
				'name'    => 'taxonomy_read_more_label',
				'label'   => esc_html__( 'Read More Label', 'dinofolio' ),
				'default' => esc_html__( 'View Project', 'dinofolio' ),
			),
			array(
				'type'    => 'checkbox',
				'id'      => 'taxonomy_lightbox',
				'name'    => 'taxonomy_lightbox',
				'label'   => esc_html__( 'Enable Lightbox', 'dinofolio' ),
				'default' => true,
			),
			array(
				'type'    => 'checkbox',
				'id'      => 'taxonomy_show_pagination',
				'name'    => 'taxonomy_show_pagination',
				'label'   => esc_html__( 'Show Pagination', 'dinofolio' ),
				'default' => true,
			),
			array(
				'type'        => 'subsection',
				'id'          => 'taxonomy_query_subsection',
				'name'        => 'taxonomy_query_subsection',
				'label'       => esc_html__( 'Query', 'dinofolio' ),
				'description' => esc_html__( 'Posts are automatically limited to the current category or tag archive.', 'dinofolio' ),
			),
			array(
				'type'    => 'number',
				'id'      => 'taxonomy_posts_per_page',
				'name'    => 'taxonomy_posts_per_page',
				'label'   => esc_html__( 'Posts To Show', 'dinofolio' ),
				'min'     => 1,
				'max'     => 100,
				'default' => 12,
			),
			array(
				'type'    => 'select',
				'id'      => 'taxonomy_order_by',
				'name'    => 'taxonomy_order_by',
				'label'   => esc_html__( 'Order By', 'dinofolio' ),
				'options' => array(
					'menu_order' => esc_html__( 'Default (Menu Order)', 'dinofolio' ),
					'date'       => esc_html__( 'Date', 'dinofolio' ),
					'title'      => esc_html__( 'Title', 'dinofolio' ),
					'modified'   => esc_html__( 'Last Modified', 'dinofolio' ),
					'rand'       => esc_html__( 'Random', 'dinofolio' ),
				),
				'default' => 'date',
			),
			array(
				'type'    => 'select',
				'id'      => 'taxonomy_order',
				'name'    => 'taxonomy_order',
				'label'   => esc_html__( 'Order', 'dinofolio' ),
				'options' => array(
					'desc' => esc_html__( 'Descending', 'dinofolio' ),
					'asc'  => esc_html__( 'Ascending', 'dinofolio' ),
				),
				'default' => 'desc',
			),
			array(
				'type'        => 'subsection',
				'id'          => 'taxonomy_style_subsection',
				'name'        => 'taxonomy_style_subsection',
				'label'       => esc_html__( 'Style', 'dinofolio' ),
				'description' => esc_html__( 'Same styling options as the portfolio listing block and Elementor widget.', 'dinofolio' ),
			),
		);

		return array_merge( $fields, $this->get_listing_style_setting_fields( 'taxonomy_' ) );
	}

	/**
	 * Related projects style options with preview images.
	 *
	 * @return array
	 */
	private function get_related_projects_style_options() {
		$base = DINOFOLIO_URL . 'includes/admin/assets/images/';

		return array(
			'grid' => array(
				'label' => esc_html__( 'Grid', 'dinofolio' ),
				'image' => $base . 'related-works-style-1.svg',
			),
			'carousel' => array(
				'label' => esc_html__( 'Carousel', 'dinofolio' ),
				'image' => $base . 'related-works-style-2.svg',
			),
		);
	}

	/**
	 * Get ordered image sizes: WordPress common sizes first, then theme/custom sizes.
	 *
	 * @return array
	 */
	private function get_ordered_image_sizes() {
		global $_wp_additional_image_sizes;

		$sizes       = get_intermediate_image_sizes();
		$common      = array( 'thumbnail', 'medium', 'medium_large', 'large' );
		$sorted      = array();
		$remaining   = array_diff( $sizes, $common );
		$final_order = array_merge( array_intersect( $common, $sizes ), array( 'dinofolio-featured-1200x900' ), $remaining, array( 'full' ) );

		foreach ( $final_order as $size ) {
			if ( isset( $sorted[ $size ] ) ) {
				continue;
			}

			if ( 'full' === $size ) {
				$sorted[ $size ] = esc_html__( 'Full', 'dinofolio' );
				continue;
			}

			if ( 'dinofolio-featured-1200x900' === $size ) {
				$sorted[ $size ] = esc_html__( 'Featured 1200 x 900', 'dinofolio' );
				continue;
			}

			$label  = ucwords( str_replace( array( '-', '_' ), ' ', $size ) );
			$width  = 0;
			$height = 0;

			if ( in_array( $size, array( 'thumbnail', 'medium', 'medium_large', 'large' ), true ) ) {
				$width  = (int) get_option( "{$size}_size_w", 0 );
				$height = (int) get_option( "{$size}_size_h", 0 );
			} elseif ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
				$width  = isset( $_wp_additional_image_sizes[ $size ]['width'] ) ? (int) $_wp_additional_image_sizes[ $size ]['width'] : 0;
				$height = isset( $_wp_additional_image_sizes[ $size ]['height'] ) ? (int) $_wp_additional_image_sizes[ $size ]['height'] : 0;
			}

			if ( $width > 0 || $height > 0 ) {
				$label .= sprintf( ' (%d x %d)', $width, $height );
			}

			$sorted[ $size ] = $label;
		}

		return $sorted;
	}

	/**
	 * Render a field based on its type
	 */
	private function render_field( $field ) {
		// Ensure field renderer is initialized
		if ( ! $this->field_renderer ) {
			$this->field_renderer = new DinoFolio_Field_Renderer( $this );
		}
		
		$this->field_renderer->render_field( $field, self::OPTION_NAME );
	}

	/**
	 * Render tools section content
	 */
	private function render_tools_section() {
		?>
		<!-- Import/Export Tools -->
		<div class="wpdino-tools-grid">
			<div class="wpdino-tool-item">
				<div class="wpdino-tool-icon">
					<span class="dashicons dashicons-download"></span>
				</div>
				<div class="wpdino-tool-content">
					<h4><?php esc_html_e( 'Export Settings', 'dinofolio' ); ?></h4>
					<p><?php esc_html_e( 'Download your current settings as a JSON file.', 'dinofolio' ); ?></p>
					<button type="button" id="export-settings" class="wpdino-btn wpdino-btn-secondary">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Export Settings', 'dinofolio' ); ?>
					</button>
				</div>
			</div>

			<div class="wpdino-tool-item">
				<div class="wpdino-tool-icon">
					<span class="dashicons dashicons-upload"></span>
				</div>
				<div class="wpdino-tool-content">
					<h4><?php esc_html_e( 'Import Settings', 'dinofolio' ); ?></h4>
					<p><?php esc_html_e( 'Upload a settings file to restore your configuration.', 'dinofolio' ); ?></p>
					<div class="wpdino-file-upload">
						<input type="file" id="import-file" accept=".json" style="display: none;" />
						<button type="button" id="import-settings" class="wpdino-btn wpdino-btn-secondary">
							<span class="dashicons dashicons-upload"></span>
							<?php esc_html_e( 'Choose File', 'dinofolio' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- System Information -->
		<div class="wpdino-section">
			<h3 class="wpdino-section-title">
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'System Information', 'dinofolio' ); ?>
			</h3>
			<p class="wpdino-section-description"><?php esc_html_e( 'Copy this information when contacting support for faster troubleshooting.', 'dinofolio' ); ?></p>
			
			<div class="wpdino-system-info">
				<div class="wpdino-system-info-header">
					<button type="button" id="copy-system-info" class="wpdino-btn wpdino-btn-secondary">
						<span class="dashicons dashicons-admin-page"></span>
						<?php esc_html_e( 'Copy System Info', 'dinofolio' ); ?>
					</button>
				</div>
				<textarea id="system-info-content" class="wpdino-system-info-content" readonly><?php echo esc_textarea( $this->get_system_info() ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Get system information for debugging
	 */
	private function get_system_info() {
		global $wpdb;
		
		// Include plugin functions if not already loaded
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		$info = '### DinoFolio System Info ###' . "\n\n";
		
		// WordPress & Server Info
		$info .= '--- WordPress & Server ---' . "\n";
		$info .= 'Site URL: ' . site_url() . "\n";
		$info .= 'Home URL: ' . home_url() . "\n";
		$info .= 'WordPress Version: ' . get_bloginfo( 'version' ) . "\n";
		$info .= 'PHP Version: ' . PHP_VERSION . "\n";
		$info .= 'MySQL Version: ' . $wpdb->db_version() . "\n";
		$info .= 'Server: ' . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unknown' ) . "\n";
		$info .= 'WP_DEBUG: ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
		$info .= 'Memory Limit: ' . WP_MEMORY_LIMIT . "\n";
		$info .= 'Max Upload Size: ' . size_format( wp_max_upload_size() ) . "\n";
		$info .= 'Max Execution Time: ' . ini_get( 'max_execution_time' ) . ' seconds' . "\n";
		$info .= "\n";
		
		// Image Settings
		$info .= '--- Image Settings ---' . "\n";
		$info .= 'Thumbnail Size: ' . get_option( 'thumbnail_size_w' ) . 'x' . get_option( 'thumbnail_size_h' ) . "\n";
		$info .= 'Medium Size: ' . get_option( 'medium_size_w' ) . 'x' . get_option( 'medium_size_h' ) . "\n";
		$info .= 'Large Size: ' . get_option( 'large_size_w' ) . 'x' . get_option( 'large_size_h' ) . "\n";
		$info .= "\n";
		
		// Plugin Info
		$info .= '--- Plugin Info ---' . "\n";
		$info .= 'DinoFolio Version: ' . DINOFOLIO_VERSION . "\n"; 
		$info .= 'DinoFolio URL: ' . DINOFOLIO_URL . "\n";
		$info .= 'DinoFolio PRO: ' . ( defined( 'DINOFOLIO_PRO_VERSION' ) ? DINOFOLIO_PRO_VERSION : 'Not Installed' ) . "\n"; 
		$info .= "\n";
		
		// Theme Info
		$info .= '--- Active Theme ---' . "\n";
		$theme = wp_get_theme();
		$info .= 'Name: ' . $theme->get( 'Name' ) . "\n";
		$info .= 'Version: ' . $theme->get( 'Version' ) . "\n";
		$info .= 'Author: ' . $theme->get( 'Author' ) . "\n";
		$info .= "\n";
		
		// Active Plugins
		$info .= '--- Active Plugins ---' . "\n";
		$plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins' );
		
		foreach ( $plugins as $plugin_file => $plugin_data ) {
			if ( in_array( $plugin_file, $active_plugins ) ) {
				$info .= $plugin_data['Name'] . ' v' . $plugin_data['Version'] . ' (' . $plugin_file . ')' . "\n";
			}
		}
		$info .= "\n";
		
		// DinoFolio Settings
		$info .= '--- DinoFolio Settings ---' . "\n";
		$settings = self::get_all_settings();
		
		// Define password field IDs
		$password_fields = ['dinofolio_mailchimp_api_key', 'openai_api_key'];
		
		foreach ( $settings as $key => $value ) {
			// Mask password values
			if ( in_array( $key, $password_fields ) && ! empty( $value ) ) {
				$value = str_repeat( '*', strlen( $value ) );
			}
			
			if ( is_array( $value ) ) {
				$info .= $key . ': ' . implode( ', ', $value ) . "\n";
			} else {
				$info .= $key . ': ' . $value . "\n";
			}
		}
		
		return $info;
	}

	/**
	 * Settings page content
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wpdino-settings-wrap">
			
			<!-- Header -->
			<div class="wpdino-header">
				<div class="wpdino-header-content">
					<div class="wpdino-header-content-left">
						<h1>
							<?php 
						/**
						 * Filter the plugin name displayed in settings header.
						 *
						 * @since 1.0.0
						 * @param string $name Plugin name.
						 */
						$dinofolio_name = apply_filters( 'dinofolio_name', esc_html__( 'Welcome to DinoFolio!', 'dinofolio' ) );
							
						echo wp_kses_post( $dinofolio_name );
							?>
						</h1>
						<p class="wpdino-tagline"><?php esc_html_e( 'Turn your projects into a powerful visual story.', 'dinofolio' ); ?></p>
					</div>
					<div class="wpdino-header-content-right">
						<span class="wpdino-version">
							<?php esc_html_e('v', 'dinofolio'); ?>
							<?php
							/**
							 * Filter the plugin version displayed in settings header.
							 *
							 * @since 1.0.0
							 * @param string $version Plugin version.
							 */
							$dinofolio_version = apply_filters( 'dinofolio_version', DINOFOLIO_VERSION );
							echo esc_html( $dinofolio_version );
							?>
						</span>
					</div>
				</div>
			</div>

			<!-- Admin Notices -->
			<?php $this->render_admin_notices(); ?>

			<div class="wpdino-main<?php echo class_exists( 'DinoFolioPro\Plugin' ) ? ' wpdino-main-no-sidebar' : ''; ?>">
				
				<!-- Settings Content -->
				<div class="wpdino-content">
					<div class="wpdino-card">
						
						<!-- Tab Navigation -->
						<div class="wpdino-tabs-nav" role="tablist" aria-label="<?php esc_attr_e( 'Settings tabs', 'dinofolio' ); ?>">
							<?php
							$sections = $this->get_settings_sections();
							$first_section = true;
							foreach ( $sections as $section_id => $section ) :
								$is_active = $first_section ? 'active' : '';
								$aria_selected = $first_section ? 'true' : 'false';
								$first_section = false;
							?>
							<button type="button" class="wpdino-tab-btn <?php echo esc_attr( $is_active ); ?>" data-tab="<?php echo esc_attr( $section_id ); ?>" role="tab" aria-selected="<?php echo esc_attr( $aria_selected ); ?>" aria-controls="tab-<?php echo esc_attr( $section_id ); ?>" id="tab-<?php echo esc_attr( $section_id ); ?>-btn">
								<span class="dashicons <?php echo esc_attr( $section['icon'] ); ?>"></span>
								<?php echo esc_html( $section['title'] ); ?>
							</button>
							<?php endforeach; ?>
						</div>
						
						<form method="post" action="" class="wpdino-form" autocomplete="off">
							<?php wp_nonce_field( 'wpdino_settings_save', 'wpdino_settings_nonce' ); ?>
							
							<?php
							$sections = $this->get_settings_sections();
							$first_section = true;
							foreach ( $sections as $section_id => $section ) :
								$is_active = $first_section ? 'active' : '';
								$first_section = false;
							?>
							<!-- <?php echo esc_html( $section['title'] ); ?> Settings Tab -->
							<div class="wpdino-tab-content <?php echo esc_attr( $is_active ); ?>" id="tab-<?php echo esc_attr( $section_id ); ?>" role="tabpanel" aria-labelledby="tab-<?php echo esc_attr( $section_id ); ?>-btn">
								
								<?php if ( ! empty( $section['title'] ) || ! empty( $section['description'] ) ) : ?>
								<!-- Section Header -->
								<div class="wpdino-section">
									<?php if ( ! empty( $section['title'] ) ) : ?>
									<h3 class="wpdino-section-title">
										<span class="dashicons <?php echo esc_attr( $section['icon'] ); ?>"></span>
										<?php echo esc_html( $section['title'] ); ?>
									</h3>
									<?php endif; ?>
									<?php if ( ! empty( $section['description'] ) ) : ?>
									<p class="wpdino-section-description"><?php echo wp_kses_post( $section['description'] ); ?></p>
									<?php endif; ?>
								</div>
								<?php endif; ?>
								
								<?php
								if ( ! empty( $section['fields'] ) ) {
									$in_widgets_section = false;
									$in_pro_widgets_section = false;
									$widget_fields_started = false;
									
									foreach ( $section['fields'] as $field ) {
										// Check if we're entering the widgets subsection
										if ( isset( $field['type'] ) && $field['type'] === 'subsection' ) {
											if ( isset( $field['id'] ) && $field['id'] === 'widgets_subsection' ) {
												$in_widgets_section = true;
												$in_pro_widgets_section = false;
											} elseif ( isset( $field['id'] ) && $field['id'] === 'pro_widgets_subsection' ) {
												$in_pro_widgets_section = true;
												$in_widgets_section = false;
												// Close previous widget grid if open
												if ( $widget_fields_started ) {
													echo '</div>';
													$widget_fields_started = false;
												}
											}
										}
										
										// Check if this is a widget field (free or pro)
										$is_widget_field = ( isset( $field['id'] ) && ( strpos( $field['id'], 'widget_enable_' ) === 0 || strpos( $field['id'], 'widget_pro_' ) === 0 ) );
										
										// Open wrapper when first widget field is encountered
										if ( ( $in_widgets_section || $in_pro_widgets_section ) && $is_widget_field && ! $widget_fields_started ) {
											echo '<div class="wpdino-widgets-grid-wrapper">';
											$widget_fields_started = true;
										}
										
										// Close wrapper if we've left widget fields (next non-widget field after widgets)
										if ( $widget_fields_started && ! $is_widget_field && isset( $field['type'] ) && $field['type'] !== 'subsection' ) {
											echo '</div>';
											$widget_fields_started = false;
											$in_widgets_section = false;
											$in_pro_widgets_section = false;
										}
										
										$this->render_field( $field );
									}
									
									// Close wrapper if still open at the end
									if ( $widget_fields_started ) {
										echo '</div>';
									}
								}
								
								if ( ! empty( $section['callback'] ) && is_callable( $section['callback'] ) ) {
									call_user_func( $section['callback'] );
								}
								?>
								
							</div>
							<?php endforeach; ?>

							<!-- Form Footer - Always Visible -->
							<div class="wpdino-form-footer">
								<div class="wpdino-form-actions-left">
									<button type="submit" class="wpdino-btn wpdino-btn-primary">
										<span class="dashicons dashicons-yes"></span>
										<?php esc_html_e( 'Save Changes', 'dinofolio' ); ?>
									</button>
								</div>
								<div class="wpdino-form-actions-right">
									<button type="button" id="reset-settings" class="wpdino-btn wpdino-btn-danger">
										<span class="dashicons dashicons-backup"></span>
										<?php esc_html_e( 'Reset to Defaults', 'dinofolio' ); ?>
									</button>
								</div>
							</div>
						</form>
					</div>
				</div>
				
				<!-- Sidebar -->
				<div class="wpdino-sidebar">
					<?php $this->render_pro_upsell(); ?>
				</div>
			</div>

			<!-- Footer -->
			<div class="wpdino-footer">
				<div class="wpdino-footer-content">
					<div class="wpdino-footer-left">
						<?php
						$footer_left_content = sprintf(
							'<p>%s</p>',
							sprintf(
								/* translators: %1$s: Plugin version number. %2$s: Author name linked to the WPDINO website. */
								esc_html__( 'DinoFolio Lite v%1$s by %2$s', 'dinofolio' ),
								esc_attr( DINOFOLIO_VERSION ),
								'<a href="' . esc_url( $this->add_utm_params( 'https://wpdino.com', 'footer_brand_link' ) ) . '" target="_blank">WPDINO</a>'
							)
						);
						/**
						 * Filter the footer left content.
						 *
						 * @since 1.0.0
						 *
						 * @param string $footer_left_content The footer left HTML content.
						 */
						echo wp_kses_post( apply_filters( 'dinofolio_settings_footer_left', $footer_left_content ) );
						?>
					</div>
					<div class="wpdino-footer-right">
						<?php
						$footer_right_links = sprintf(
							'<div class="wpdino-footer-links"><a href="%1$s" target="_blank">%2$s</a><span>|</span><a href="%3$s" target="_blank">%4$s</a><span>|</span><a href="%5$s" target="_blank">%6$s</a></div>',
							esc_url( $this->add_utm_params( 'https://wpdino.com', 'footer_home_link' ) ),
							esc_html__( 'WPDINO', 'dinofolio' ),
							esc_url( $this->add_utm_params( 'https://wpdino.com/docs/dinofolio/', 'footer_documentation' ) ),
							esc_html__( 'Documentation', 'dinofolio' ),
							esc_url( $this->add_utm_params( 'https://wordpress.org/support/plugin/dinofolio/', 'footer_support' ) ),
							esc_html__( 'Support', 'dinofolio' )
						);

						// Social links (Facebook, X, Instagram).
						$footer_social_links = sprintf(
							'<div class="wpdino-footer-social">
								<a href="%1$s" target="_blank" aria-label="%4$s"><span class="dashicons dashicons-facebook-alt"></span></a>
								<a href="%2$s" target="_blank" aria-label="%5$s"><span class="dashicons dashicons-twitter"></span></a>
								<a href="%3$s" target="_blank" aria-label="%6$s"><span class="dashicons dashicons-camera"></span></a>
							</div>',
							esc_url( 'https://www.facebook.com/wpdinocom' ),
							esc_url( 'https://x.com/wpdinocom' ),
							esc_url( 'https://www.instagram.com/_wpdino_/' ),
							esc_attr__( 'Follow WPDINO on Facebook', 'dinofolio' ),
							esc_attr__( 'Follow WPDINO on X (Twitter)', 'dinofolio' ),
							esc_attr__( 'Follow WPDINO on Instagram', 'dinofolio' )
						);

						$footer_right_content = $footer_right_links . $footer_social_links;

						/**
						 * Filter the footer right links content.
						 *
						 * @since 1.0.0
						 *
						 * @param string $footer_right_links The footer right links HTML content.
						 */
						echo wp_kses_post( apply_filters( 'dinofolio_settings_footer_right', $footer_right_content ) );
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
