<?php
/**
 * DinoFolio Field Renderer 
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
 * Class for rendering different field types.
 */
class DinoFolio_Field_Renderer {

	/**
	 * Settings instance for getting values
	 */
	private $settings_instance;

	/**
	 * Constructor
	 */
	public function __construct( $settings_instance = null ) {
		$this->settings_instance = $settings_instance;
	}

	/**
	 * Render a field based on its type
	 */
	public function render_field( $field, $option_name = '' ) {
		// Handle row type first (doesn't need id/name)
		if ( isset( $field['type'] ) && $field['type'] === 'row' ) {
			$this->render_row_field( $field, $option_name );
			return;
		}

		// Handle subsection type (doesn't need value)
		if ( isset( $field['type'] ) && $field['type'] === 'subsection' ) {
			$this->render_subsection_field( $field );
			return;
		}

		// For all other field types, ensure id and name exist
		if ( ! isset( $field['id'] ) || ! isset( $field['name'] ) ) {
			return;
		}

		$value = $this->get_field_value( $field['id'] );
		$field_id = esc_attr( $field['id'] );
		$field_name = ! empty( $option_name ) ? $option_name . '[' . esc_attr( $field['name'] ) . ']' : esc_attr( $field['name'] );
		$field_class = isset( $field['class'] ) ? esc_attr( $field['class'] ) : '';

		switch ( $field['type'] ) {
			case 'text':
				$this->render_text_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'password':
				$this->render_password_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'number':
				$this->render_number_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'textarea':
				$this->render_textarea_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'select':
				$this->render_select_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'checkbox':
				$this->render_checkbox_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'colorpicker':
				$this->render_colorpicker_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'radio':
				$this->render_radio_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'toggle_radio':
				$this->render_toggle_radio_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'image_select':
				$this->render_image_select_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'url':
				$this->render_url_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'email':
				$this->render_email_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'range':
				$this->render_range_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'file':
				$this->render_file_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'multiple_select':
				$this->render_multiple_select_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			case 'editor':
				$this->render_editor_field( $field, $field_id, $field_name, $value, $field_class );
				break;

			default:
				// Allow custom field types via action hook
				do_action( 'wpdino_portfolio_render_field_' . $field['type'], $field, $field_id, $field_name, $value, $field_class );
				break;
		}
	}

	/**
	 * Get field value from settings
	 */
	private function get_field_value( $field_id ) {
		if ( $this->settings_instance && method_exists( $this->settings_instance, 'get_setting' ) ) {
			return $this->settings_instance->get_setting( $field_id );
		}
		return '';
	}

	/**
	 * Render row field (groups multiple fields)
	 */
	private function render_row_field( $field, $option_name ) {
		?>
		<div class="wpdino-field-row">
			<?php
			if ( ! empty( $field['fields'] ) ) {
				foreach ( $field['fields'] as $row_field ) {
					$this->render_field( $row_field, $option_name );
				}
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render subsection field (displays a subsection title)
	 *
	 * @since 1.0.2
	 * @param array $field Field configuration array
	 */
	private function render_subsection_field( $field ) {
		$is_widgets_subsection = ( isset( $field['id'] ) && $field['id'] === 'widgets_subsection' );
		?>
		<div class="wpdino-subsection<?php echo $is_widgets_subsection ? ' wpdino-widgets-subsection' : ''; ?>">
			<?php if ( ! empty( $field['label'] ) ) : ?>
			<h3 class="wpdino-subsection-title">
				<?php echo esc_html( $field['label'] ); ?>
			</h3>
			<?php endif; ?>
			<?php if ( ! empty( $field['description'] ) ) : ?>
			<p class="wpdino-subsection-description"><?php echo wp_kses_post( $field['description'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render text field
	 */
	private function render_text_field( $field, $field_id, $field_name, $value, $field_class ) {
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<input type="text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" 
				   value="<?php echo esc_attr( $value ); ?>" 
				   class="wpdino-input <?php echo esc_attr( $field_class ); ?>"
				   <?php if ( isset( $field['placeholder'] ) ) echo 'placeholder="' . esc_attr( $field['placeholder'] ) . '"'; ?> />
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render password field (using text input with JavaScript masking to avoid browser password manager)
	 */
	private function render_password_field( $field, $field_id, $field_name, $value, $field_class ) {
		// Mask the value for display if it exists
		$display_value = '';
		$is_masked = false;
		if ( ! empty( $value ) ) {
			$display_value = str_repeat( '•', strlen( $value ) );
			$is_masked = true;
		}
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<div class="wpdino-password-wrapper">
				<input type="text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" 
					   value="<?php echo esc_attr( $display_value ); ?>" 
					   class="wpdino-input wpdino-password-input <?php echo esc_attr( $field_class ); ?>"
					   autocomplete="off"
					   data-lpignore="true"
					   data-form-type="other"
					   data-masked="<?php echo $is_masked ? 'true' : 'false'; ?>"
					   data-actual-value="<?php echo esc_attr( $value ); ?>"
					   <?php if ( isset( $field['placeholder'] ) ) echo 'placeholder="' . esc_attr( $field['placeholder'] ) . '"'; ?> />
				<span class="wpdino-password-toggle" data-target="#<?php echo esc_attr( $field_id ); ?>">
					<span class="dashicons dashicons-visibility"></span>
					<span class="dashicons dashicons-hidden"></span>
				</span>
			</div>
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render number field
	 */
	private function render_number_field( $field, $field_id, $field_name, $value, $field_class ) {
		$min = isset( $field['min'] ) ? intval( $field['min'] ) : '';
		$max = isset( $field['max'] ) ? intval( $field['max'] ) : '';
		$step = isset( $field['step'] ) ? floatval( $field['step'] ) : '';
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<input type="number" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" 
				   value="<?php echo esc_attr( $value ); ?>" 
				   <?php if ( $min !== '' ) echo 'min="' . esc_attr( $min ) . '"'; ?>
				   <?php if ( $max !== '' ) echo 'max="' . esc_attr( $max ) . '"'; ?>
				   <?php if ( $step !== '' ) echo 'step="' . esc_attr( $step ) . '"'; ?>
				   class="wpdino-input <?php echo esc_attr( $field_class ); ?>" />
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render textarea field
	 */
	private function render_textarea_field( $field, $field_id, $field_name, $value, $field_class ) {
		$rows = isset( $field['rows'] ) ? intval( $field['rows'] ) : 4;
		$placeholder = isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<textarea id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" 
					  rows="<?php echo esc_attr( $rows ); ?>" 
					  class="wpdino-textarea <?php echo esc_attr( $field_class ); ?>"
					  <?php if ( $placeholder ) echo 'placeholder="' . esc_attr( $placeholder ) . '"'; ?>><?php echo esc_textarea( $value ); ?></textarea>
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render select field
	 */
	private function render_select_field( $field, $field_id, $field_name, $value, $field_class ) {
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" class="wpdino-select <?php echo esc_attr( $field_class ); ?>">
				<?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
						<?php echo esc_html( $option_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render checkbox field
	 */
	private function render_checkbox_field( $field, $field_id, $field_name, $value, $field_class ) {
		// Check if this is a widget field
		$is_widget_field = ( strpos( $field_id, 'widget_enable_' ) === 0 || strpos( $field_id, 'widget_pro_' ) === 0 );
		$is_pro_widget = isset( $field['is_pro'] ) && $field['is_pro'];
		$is_disabled = isset( $field['disabled'] ) && $field['disabled'];
		
		$field_group_class = $is_widget_field ? ' wpdino-widget-field' : '';
		if ( $is_pro_widget ) {
			$field_group_class .= ' wpdino-pro-widget-field';
		}
		?>
		<div class="wpdino-field-group<?php echo esc_attr( $field_group_class ); ?>">
			<label class="wpdino-checkbox<?php echo $is_disabled ? ' wpdino-checkbox-disabled' : ''; ?>">
				<input type="checkbox" name="<?php echo esc_attr( $field_name ); ?>"
					   <?php checked( $value ); ?>
					   <?php echo $is_disabled ? 'disabled="disabled"' : ''; ?>
					   <?php if ( $is_pro_widget ) : ?>
					   data-pro-widget="true"
					   <?php endif; ?> />
				<span class="wpdino-checkbox-mark"></span>
				<span class="wpdino-checkbox-label">
					<?php echo esc_html( $field['label'] ); ?>
					<?php if ( $is_pro_widget ) : ?>
						<span class="wpdino-pro-badge-small"><?php esc_html_e( 'PRO', 'dinofolio' ); ?></span>
					<?php endif; ?>
				</span>
			</label>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="wpdino-field-description wpdino-widget-description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render colorpicker field
	 */
	private function render_colorpicker_field( $field, $field_id, $field_name, $value, $field_class ) {
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<input type="text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" 
				   value="<?php echo esc_attr( $value ); ?>" 
				   class="wpdino-color-picker <?php echo esc_attr( $field_class ); ?>" 
				   data-default-color="<?php echo esc_attr( isset( $field['default'] ) ? $field['default'] : '' ); ?>" />
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render radio field
	 */
	private function render_radio_field( $field, $field_id, $field_name, $value, $field_class ) {
		?>
		<div class="wpdino-field-group">
			<label class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<div class="wpdino-radio-group">
				<?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
					<label class="wpdino-radio">
						<input type="radio" name="<?php echo esc_attr( $field_name ); ?>" 
							   value="<?php echo esc_attr( $option_value ); ?>" 
							   <?php checked( $value, $option_value ); ?> />
						<span class="wpdino-radio-mark"></span>
						<?php echo esc_html( $option_label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render toggle radio field.
	 *
	 * Styled segmented radio (same visual system used in portfolio meta box),
	 * while keeping the default radio type available for future settings.
	 */
	private function render_toggle_radio_field( $field, $field_id, $field_name, $value, $field_class ) {
		?>
		<div class="wpdino-field-group">
			<label class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<div class="wpdino-toggle-group <?php echo esc_attr( $field_class ); ?>">
				<?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
					<label class="<?php echo ( (string) $value === (string) $option_value ) ? 'is-checked' : ''; ?>">
						<input type="radio"
							   name="<?php echo esc_attr( $field_name ); ?>"
							   value="<?php echo esc_attr( $option_value ); ?>"
							   <?php checked( $value, $option_value ); ?> />
						<?php echo esc_html( $option_label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render image select field
	 */
	private function render_image_select_field( $field, $field_id, $field_name, $value, $field_class ) {
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<div class="wpdino-image-select-group wpdino-image-select-group--compact">
				<?php foreach ( $field['options'] as $option_value => $option_data ) : ?>
					<label class="wpdino-image-select-option">
						<input type="radio" name="<?php echo esc_attr( $field_name ); ?>" 
							   value="<?php echo esc_attr( $option_value ); ?>" 
							   <?php checked( $value, $option_value ); ?> />
						<img src="<?php echo esc_url( $option_data['image'] ); ?>" alt="<?php echo esc_attr( $option_data['label'] ); ?>" />
						<span class="wpdino-image-select-label"><?php echo esc_html( $option_data['label'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render URL field
	 */
	private function render_url_field( $field, $field_id, $field_name, $value, $field_class ) {
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<input type="url" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" 
				   value="<?php echo esc_attr( $value ); ?>" 
				   class="wpdino-input <?php echo esc_attr( $field_class ); ?>" 
				   placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : 'https://' ); ?>" />
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render email field
	 */
	private function render_email_field( $field, $field_id, $field_name, $value, $field_class ) {
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<input type="email" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" 
				   value="<?php echo esc_attr( $value ); ?>" 
				   class="wpdino-input <?php echo esc_attr( $field_class ); ?>" 
				   placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : 'email@example.com' ); ?>" />
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render range field
	 */
	private function render_range_field( $field, $field_id, $field_name, $value, $field_class ) {
		$min = isset( $field['min'] ) ? intval( $field['min'] ) : 0;
		$max = isset( $field['max'] ) ? intval( $field['max'] ) : 100;
		$step = isset( $field['step'] ) ? floatval( $field['step'] ) : 1;
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
				<span class="wpdino-range-value" id="<?php echo esc_attr( $field_id ); ?>-value"><?php echo esc_html( $value ); ?></span>
			</label>
			<input type="range" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" 
				   value="<?php echo esc_attr( $value ); ?>" 
				   min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>"
				   class="wpdino-range <?php echo esc_attr( $field_class ); ?>" 
				   oninput="document.getElementById('<?php echo esc_attr( $field_id ); ?>-value').textContent = this.value" />
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render file field (Image only with preview)
	 */
	private function render_file_field( $field, $field_id, $field_name, $value, $field_class ) {
		$media_title = isset( $field['media_title'] ) ? $field['media_title'] : esc_html__( 'Select Image', 'dinofolio' );
		$media_button = isset( $field['media_button'] ) ? $field['media_button'] : esc_html__( 'Use This Image', 'dinofolio' );
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<div class="wpdino-image-field">
				<!-- Hidden input to store the image URL -->
				<input type="hidden" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" 
					   value="<?php echo esc_attr( $value ); ?>" class="<?php echo esc_attr( $field_class ); ?>" />
				
				<!-- Image preview -->
				<div class="wpdino-image-preview" <?php echo empty( $value ) ? 'style="display: none;"' : ''; ?>>
					<img src="<?php echo esc_url( $value ); ?>" alt="<?php esc_attr_e( 'Selected image', 'dinofolio' ); ?>" />
					<div class="wpdino-image-overlay">
						<button type="button" class="wpdino-btn wpdino-btn-small wpdino-btn-secondary wpdino-image-change" 
								data-target="#<?php echo esc_attr( $field_id ); ?>" 
								data-title="<?php echo esc_attr( $media_title ); ?>"
								data-button="<?php echo esc_attr( $media_button ); ?>">
							<span class="dashicons dashicons-edit"></span>
							<?php esc_html_e( 'Change', 'dinofolio' ); ?>
						</button>
						<button type="button" class="wpdino-btn wpdino-btn-small wpdino-btn-danger wpdino-image-remove" 
								data-target="#<?php echo esc_attr( $field_id ); ?>">
							<span class="dashicons dashicons-no-alt"></span>
							<?php esc_html_e( 'Remove', 'dinofolio' ); ?>
						</button>
					</div>
				</div>
				
				<!-- Upload prompt (shown when no image) -->
				<div class="wpdino-image-upload-prompt" <?php echo ! empty( $value ) ? 'style="display: none;"' : ''; ?>>
					<div class="wpdino-upload-icon">
						<span class="dashicons dashicons-format-image"></span>
					</div>
					<p><?php esc_html_e( 'No image selected', 'dinofolio' ); ?></p>
					<button type="button" class="wpdino-btn wpdino-btn-primary wpdino-image-upload" 
							data-target="#<?php echo esc_attr( $field_id ); ?>" 
							data-title="<?php echo esc_attr( $media_title ); ?>"
							data-button="<?php echo esc_attr( $media_button ); ?>">
						<span class="dashicons dashicons-upload"></span>
						<?php esc_html_e( 'Select Image', 'dinofolio' ); ?>
					</button>
				</div>
			</div>
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render multiple select field
	 */
	private function render_multiple_select_field( $field, $field_id, $field_name, $value, $field_class ) {
		$selected_values = is_array( $value ) ? $value : array();
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>[]" multiple 
					class="wpdino-select <?php echo esc_attr( $field_class ); ?>" 
					size="<?php echo esc_attr( isset( $field['size'] ) ? $field['size'] : 4 ); ?>">
				<?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( in_array( $option_value, $selected_values ) ); ?>>
						<?php echo esc_html( $option_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render editor field
	 */
	private function render_editor_field( $field, $field_id, $field_name, $value, $field_class ) {
		$editor_settings = array(
			'textarea_name' => $field_name,
			'textarea_rows' => isset( $field['rows'] ) ? $field['rows'] : 10,
			'teeny' => isset( $field['teeny'] ) ? $field['teeny'] : false,
			'media_buttons' => isset( $field['media_buttons'] ) ? $field['media_buttons'] : true,
		);
		?>
		<div class="wpdino-field-group">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="wpdino-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<?php wp_editor( $value, $field_id, $editor_settings ); ?>
			<?php $this->render_field_description( $field ); ?>
		</div>
		<?php
	}

	/**
	 * Render field description
	 */
	private function render_field_description( $field ) {
		if ( ! empty( $field['description'] ) ) : ?>
			<p class="wpdino-description"><?php echo wp_kses_post( $field['description'] ); ?></p>
		<?php endif;
	}
} 