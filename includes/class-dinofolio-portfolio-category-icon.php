<?php
/**
 * Portfolio category icon term meta and rendering.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Stores and renders per-category icons for portfolio listings.
 */
class Portfolio_Category_Icon {

	const TAXONOMY       = 'wpdino_portfolio_category';
	const META_KEY       = 'dinofolio_category_icon';
	const DEFAULT_PRESET = 'none';

	/**
	 * Bootstrap hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_term_meta' ) );
		add_action( self::TAXONOMY . '_add_form_fields', array( __CLASS__, 'render_add_form_fields' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( __CLASS__, 'render_edit_form_fields' ) );
		add_action( 'created_' . self::TAXONOMY, array( __CLASS__, 'save_term_meta' ) );
		add_action( 'edited_' . self::TAXONOMY, array( __CLASS__, 'save_term_meta' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register category icon term meta.
	 *
	 * @return void
	 */
	public static function register_term_meta() {
		register_term_meta(
			self::TAXONOMY,
			self::META_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_icon_value' ),
				'show_in_rest'      => true,
				'auth_callback'     => static function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);
	}

	/**
	 * Built-in icon presets.
	 *
	 * @return array<string, array{label:string,svg:string}>
	 */
	public static function get_presets() {
		return array(
			'web-design'   => array(
				'label' => esc_html__( 'Web Design', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M3 8h18"/><circle cx="6" cy="6" r="0.75" fill="currentColor" stroke="none"/><circle cx="8.5" cy="6" r="0.75" fill="currentColor" stroke="none"/><path d="M7 12h4"/><path d="M7 15h7"/></svg>',
			),
			'branding'     => array(
				'label' => esc_html__( 'Branding', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="4" y="4" width="8" height="8" rx="1.5"/><rect x="12" y="12" width="8" height="8" rx="1.5"/><path d="M9 12V9a2 2 0 0 1 2-2h5"/></svg>',
			),
			'photography'  => array(
				'label' => esc_html__( 'Photography', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 7h3l2-2h6l2 2h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>',
			),
			'illustration' => array(
				'label' => esc_html__( 'Illustration', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M4 20h2"/><path d="m14.5 5.5 4 4"/><path d="m6.9 14.1-3.2 3.2a1.5 1.5 0 0 0 0 2.1l1.1 1.1a1.5 1.5 0 0 0 2.1 0l3.2-3.2"/><path d="m13.8 6.2 3 3"/><path d="m9.7 10.3-3 3"/><path d="m18.5 3.5 2 2"/></svg>',
			),
			'motion-3d'    => array(
				'label' => esc_html__( '3D & Motion', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3 4 7.5v9L12 21l8-4.5v-9L12 3z"/><path d="m4 7.5 8 4.5 8-4.5"/><path d="M12 12v9"/><path d="m16 9.5 4-2"/><path d="m18 7.5 2 1v2"/><path d="m20 10.5-2 1"/></svg>',
			),
			'grid'    => array(
				'label' => esc_html__( 'Grid', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
			),
			'camera'  => array(
				'label' => esc_html__( 'Camera', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 7h3l2-2h6l2 2h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>',
			),
			'layers'  => array(
				'label' => esc_html__( 'Layers', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3 3 8l9 5 9-5-9-5z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/></svg>',
			),
			'brush'   => array(
				'label' => esc_html__( 'Brush', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m9.06 11.9 8.07-8.06a2.85 2.85 0 1 1 4.03 4.03l-8.06 8.07"/><path d="M3 21c2.5-4.5 6.5-8.5 11-11"/></svg>',
			),
			'code'    => array(
				'label' => esc_html__( 'Code', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
			),
			'chart'   => array(
				'label' => esc_html__( 'Chart', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 17V9"/><path d="M12 17V7"/><path d="M16 17v-5"/></svg>',
			),
			'globe'   => array(
				'label' => esc_html__( 'Globe', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15.3 15.3 0 0 1 4 9 15.3 15.3 0 0 1-4 9 15.3 15.3 0 0 1-4-9 15.3 15.3 0 0 1 4-9z"/></svg>',
			),
			'bulb'    => array(
				'label' => esc_html__( 'Idea', 'dinofolio' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/></svg>',
			),
		);
	}

	/**
	 * Preset choices shown in the admin selector (includes "None").
	 *
	 * @return array<string, array{label:string,svg:string,empty?:bool}>
	 */
	public static function get_selector_presets() {
		$none = array(
			'label' => esc_html__( 'None', 'dinofolio' ),
			'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="m8 8 8 8"/></svg>',
			'empty' => true,
		);

		return array_merge( array( 'none' => $none ), self::get_presets() );
	}

	/**
	 * Empty icon payload used when no icon should render.
	 *
	 * @return array{type:string,preset:string,attachment_id:int,url:string}
	 */
	private static function get_empty_icon_data() {
		return array(
			'type'          => 'none',
			'preset'        => 'none',
			'attachment_id' => 0,
			'url'           => '',
		);
	}

	/**
	 * Sanitize stored icon value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_icon_value( $value ) {
		$value = sanitize_text_field( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		if ( 0 === strpos( $value, 'preset:' ) ) {
			$preset = sanitize_key( substr( $value, 7 ) );
			$presets = self::get_presets();

			return isset( $presets[ $preset ] ) ? 'preset:' . $preset : '';
		}

		if ( 0 === strpos( $value, 'media:' ) ) {
			$attachment_id = absint( substr( $value, 6 ) );

			if ( $attachment_id < 1 || 'attachment' !== get_post_type( $attachment_id ) ) {
				return '';
			}

			return 'media:' . $attachment_id;
		}

		return '';
	}

	/**
	 * Get icon value for a term.
	 *
	 * @param int|\WP_Term $term Term ID or object.
	 * @return string
	 */
	public static function get_term_icon_value( $term ) {
		$term_id = $term instanceof \WP_Term ? (int) $term->term_id : absint( $term );

		if ( $term_id < 1 ) {
			return '';
		}

		$value = get_term_meta( $term_id, self::META_KEY, true );

		return self::sanitize_icon_value( $value );
	}

	/**
	 * Resolve icon data for rendering.
	 *
	 * @param int|\WP_Term $term Term ID or object.
	 * @return array{type:string,preset:string,attachment_id:int,url:string}
	 */
	public static function get_icon_data( $term ) {
		$value = self::get_term_icon_value( $term );

		if ( '' === $value ) {
			return self::get_empty_icon_data();
		}

		if ( 0 === strpos( $value, 'preset:' ) ) {
			$preset = sanitize_key( substr( $value, 7 ) );
			$presets = self::get_presets();

			if ( ! isset( $presets[ $preset ] ) ) {
				return self::get_empty_icon_data();
			}

			return array(
				'type'          => 'preset',
				'preset'        => $preset,
				'attachment_id' => 0,
				'url'           => '',
			);
		}

		$attachment_id = absint( substr( $value, 6 ) );
		$url           = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );

		if ( ! $url ) {
			return self::get_empty_icon_data();
		}

		return array(
			'type'          => 'media',
			'preset'        => '',
			'attachment_id' => $attachment_id,
			'url'           => $url,
		);
	}

	/**
	 * Render icon markup for a category term.
	 *
	 * @param int|\WP_Term $term Term ID or object.
	 * @param string       $class Extra class names.
	 * @return string
	 */
	public static function render_icon_html( $term, $class = 'dinofolio-category-pill-icon' ) {
		$data = self::get_icon_data( $term );

		if ( 'none' === $data['type'] ) {
			return '';
		}

		$classes = trim( 'dinofolio-category-icon ' . $class );

		if ( 'media' === $data['type'] && ! empty( $data['url'] ) ) {
			return '<span class="' . esc_attr( $classes ) . '"><img src="' . esc_url( $data['url'] ) . '" alt="" loading="lazy" decoding="async" /></span>';
		}

		$presets = self::get_presets();

		if ( ! isset( $presets[ $data['preset'] ] ) ) {
			return '';
		}

		return '<span class="' . esc_attr( $classes ) . '">' . $presets[ $data['preset'] ]['svg'] . '</span>';
	}

	/**
	 * Add form fields on "Add category".
	 *
	 * @return void
	 */
	public static function render_add_form_fields() {
		self::render_fields_markup( 0 );
	}

	/**
	 * Edit form fields on "Edit category".
	 *
	 * @param \WP_Term $term Current term.
	 * @return void
	 */
	public static function render_edit_form_fields( $term ) {
		self::render_fields_markup( $term->term_id, true );
	}

	/**
	 * Shared field markup for add/edit screens.
	 *
	 * @param int  $term_id Term ID (0 on add).
	 * @param bool $is_edit Whether this is the edit screen.
	 * @return void
	 */
	private static function render_fields_markup( $term_id, $is_edit = false ) {
		$value = $term_id > 0 ? self::get_term_icon_value( $term_id ) : '';
		$data  = self::get_icon_data( $term_id );
		$wrap  = $is_edit ? 'tr class="form-field"' : 'div class="form-field"';
		$tag   = $is_edit ? 'th' : 'label';
		$cell  = $is_edit ? 'td' : 'div';

		echo '<' . $wrap . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $is_edit ) {
			echo '<' . $tag . ' scope="row"><label for="dinofolio-category-icon-value">' . esc_html__( 'Category Icon', 'dinofolio' ) . '</label></' . $tag . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<' . $cell . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo '<' . $tag . ' for="dinofolio-category-icon-value">' . esc_html__( 'Category Icon', 'dinofolio' ) . '</' . $tag . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '<div class="dinofolio-category-icon-field" data-default-preset="' . esc_attr( self::DEFAULT_PRESET ) . '">';
		echo '<input type="hidden" id="dinofolio-category-icon-value" name="dinofolio_category_icon" value="' . esc_attr( $value ) . '" />';

		echo '<div class="dinofolio-category-icon-preview" aria-live="polite">';
		if ( '' === $value && 'media' !== $data['type'] ) {
			$selector_presets = self::get_selector_presets();
			echo '<span class="dinofolio-category-icon dinofolio-category-pill-icon">' . $selector_presets['none']['svg'] . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo self::render_icon_html( $term_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';

		echo '<p class="description">' . esc_html__( 'Choose a preset icon or upload a custom image. Select None to hide the category icon on portfolio listings.', 'dinofolio' ) . '</p>';

		echo '<div class="dinofolio-category-icon-presets" role="listbox" aria-label="' . esc_attr__( 'Preset category icons', 'dinofolio' ) . '">';
		foreach ( self::get_selector_presets() as $slug => $preset ) {
			$is_none       = ! empty( $preset['empty'] );
			$preset_value  = $is_none ? '' : 'preset:' . $slug;
			$selected      = $is_none
				? ( '' === $value && 'media' !== $data['type'] )
				: ( 'preset:' . $slug === $value );
			$extra_class   = $is_none ? ' is-none-preset' : '';

			echo '<button type="button" class="dinofolio-category-icon-preset' . ( $selected ? ' is-selected' : '' ) . $extra_class . '" data-value="' . esc_attr( $preset_value ) . '" role="option" aria-selected="' . ( $selected ? 'true' : 'false' ) . '" title="' . esc_attr( $preset['label'] ) . '">';
			echo '<span class="dinofolio-category-icon-preset-svg">' . $preset['svg'] . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<span class="screen-reader-text">' . esc_html( $preset['label'] ) . '</span>';
			echo '</button>';
		}
		echo '</div>';

		echo '<div class="dinofolio-category-icon-actions">';
		echo '<button type="button" class="button dinofolio-category-icon-upload">' . esc_html__( 'Upload Custom Icon', 'dinofolio' ) . '</button>';
		echo '</div>';
		echo '<p class="description dinofolio-category-icon-upload-note">';
		echo wp_kses(
			sprintf(
				/* translators: %s: SVG Support plugin URL on WordPress.org. */
				__( 'SVG files require upload support in WordPress. Install a plugin such as <a href="%s" target="_blank" rel="noopener noreferrer">SVG Support</a> before uploading SVG icons.', 'dinofolio' ),
				'https://wordpress.org/plugins/svg-support/'
			),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
					'rel'    => array(),
				),
			)
		);
		echo '</p>';

		echo '</div>';

		if ( $is_edit ) {
			echo '</' . $cell . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</' . ( $is_edit ? 'tr' : 'div' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Save term icon meta.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public static function save_term_meta( $term_id ) {
		$term_id = absint( $term_id );

		if ( $term_id < 1 ) {
			return;
		}

		$taxonomy = get_taxonomy( self::TAXONOMY );

		if ( ! $taxonomy || ! current_user_can( $taxonomy->cap->manage_terms ) ) {
			return;
		}

		if ( ! self::verify_term_save_nonce( $term_id ) ) {
			return;
		}

		if ( ! isset( $_POST['dinofolio_category_icon'] ) ) {
			return;
		}

		$value = self::sanitize_icon_value(
			sanitize_text_field( wp_unslash( $_POST['dinofolio_category_icon'] ) )
		);

		if ( '' === $value ) {
			delete_term_meta( $term_id, self::META_KEY );
			return;
		}

		update_term_meta( $term_id, self::META_KEY, $value );
	}

	/**
	 * Verify taxonomy term save nonces used by core edit-tags screens.
	 *
	 * @param int $term_id Term ID being saved.
	 * @return bool
	 */
	private static function verify_term_save_nonce( $term_id ) {
		if ( isset( $_POST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );

			if ( wp_verify_nonce( $nonce, 'update-tag_' . absint( $term_id ) ) ) {
				return true;
			}
		}

		if ( isset( $_POST['_wpnonce_add-tag'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce_add-tag'] ) );

			if ( wp_verify_nonce( $nonce, 'add-tag' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue admin assets on category screens.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen routing.
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';

		if ( self::TAXONOMY !== $taxonomy ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'dinofolio-category-icon-admin',
			DINOFOLIO_URL . 'includes/admin/assets/css/category-icon-admin.css',
			array(),
			DINOFOLIO_VERSION
		);

		wp_enqueue_script(
			'dinofolio-category-icon-admin',
			DINOFOLIO_URL . 'includes/admin/assets/js/category-icon-admin.js',
			array( 'jquery' ),
			DINOFOLIO_VERSION,
			true
		);

		$presets = array();
		foreach ( self::get_selector_presets() as $slug => $preset ) {
			$presets[ $slug ] = array(
				'label' => $preset['label'],
				'svg'   => $preset['svg'],
			);
		}

		wp_localize_script(
			'dinofolio-category-icon-admin',
			'dinofolioCategoryIconAdmin',
			array(
				'defaultPreset' => self::DEFAULT_PRESET,
				'presets'       => $presets,
				'i18n'          => array(
					'selectIcon' => esc_html__( 'Select Category Icon', 'dinofolio' ),
					'useImage'   => esc_html__( 'Use this icon', 'dinofolio' ),
				),
			)
		);
	}
}

Portfolio_Category_Icon::init();
