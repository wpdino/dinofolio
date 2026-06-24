<?php
/**
 * Portfolio Metaboxes and Single Post Meta Output.
 *
 * @package DinoFolio
 * @since 1.0.0
 */

namespace DinoFolio;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for portfolio metabox management and frontend usage.
 */
class Portfolio_Meta_Boxes {

	/**
	 * Minimum related projects to show.
	 */
	const RELATED_PROJECTS_COUNT_MIN = 2;

	/**
	 * Maximum related projects to show.
	 */
	const RELATED_PROJECTS_COUNT_MAX = 5;

	/**
	 * Singleton instance.
	 *
	 * @var Portfolio_Meta_Boxes
	 */
	private static $instance;

	/**
	 * Returns singleton instance.
	 *
	 * @return Portfolio_Meta_Boxes
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
	public function __construct() {
		Portfolio_Video_Admin::init();

		add_action( 'add_meta_boxes', array( $this, 'register_portfolio_meta_boxes' ) );
		add_action( 'save_post_wpdino_portfolio', array( $this, 'save_portfolio_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_portfolio_meta_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_single_portfolio_assets' ) );
		add_filter( 'the_content', array( $this, 'append_single_portfolio_meta_content' ) );
	}

	/**
	 * Register portfolio metabox.
	 *
	 * @return void
	 */
	public function register_portfolio_meta_boxes() {
		add_meta_box(
			'wpdino_portfolio_meta',
			esc_html__( 'Portfolio Meta', 'dinofolio' ),
			array( $this, 'render_portfolio_meta_box' ),
			'wpdino_portfolio',
			'normal',
			'high'
		);

		add_meta_box(
			'wpdino_portfolio_gallery',
			esc_html__( 'Portfolio Gallery', 'dinofolio' ),
			array( $this, 'render_portfolio_gallery_meta_box' ),
			'wpdino_portfolio',
			'normal',
			'default'
		);
	}

	/**
	 * Render metabox fields.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_portfolio_meta_box( $post ) {
		wp_nonce_field( 'wpdino_portfolio_meta_box', 'wpdino_portfolio_meta_nonce' );

		$defaults = array(
			'featured_image_display' => $this->get_settings_default( 'portfolio_meta_default_featured_image_display', 'on' ),
			'featured_image_size'    => $this->get_settings_default( 'portfolio_meta_default_featured_image_size', 'dinofolio-featured-1200x900' ),
			'related_projects'        => $this->get_settings_default( 'portfolio_meta_default_related_projects', 'on' ),
			'related_projects_style'  => $this->get_settings_default( 'portfolio_meta_default_related_projects_style', 'grid' ),
			'related_projects_title'  => $this->get_settings_default( 'portfolio_meta_default_related_projects_title', esc_html__( 'Related Projects', 'dinofolio' ) ),
			'related_projects_number' => absint( $this->get_settings_default( 'portfolio_meta_default_related_projects_number', 3 ) ),
			'date_display'         => $this->get_settings_default( 'portfolio_meta_default_date_display', 'on' ),
			'date_label'           => $this->get_settings_default( 'portfolio_meta_default_date_label', esc_html__( 'Date', 'dinofolio' ) ),
			'date_of_work'         => $this->get_settings_default( 'portfolio_meta_default_date_of_work', '' ),
			'external_url'         => $this->get_settings_default( 'portfolio_meta_default_external_url', '' ),
			'button_label'         => $this->get_settings_default( 'portfolio_meta_default_button_label', esc_html__( 'Launch', 'dinofolio' ) ),
			'attributes'           => $this->get_default_attributes_from_settings(),
		);

		$values = array();
		foreach ( $defaults as $key => $default_value ) {
			$meta_value = $this->get_stored_meta( $post->ID, $key );
			$values[ $key ] = ( '' === $meta_value ) ? $default_value : $meta_value;
		}

		if ( ! is_array( $values['attributes'] ) ) {
			$values['attributes'] = array();
		}

		$values['related_projects_number'] = $this->get_related_projects_count( $post->ID );
		$values['related_projects_style']  = $this->normalize_related_projects_style( $values['related_projects_style'] );

		$default_featured_display = isset( $defaults['featured_image_display'] ) ? $defaults['featured_image_display'] : 'on';
		$show_featured_size_row   = ( 'off' !== $values['featured_image_display'] );
		if ( 'default' === $values['featured_image_display'] ) {
			$show_featured_size_row = ( 'off' !== $default_featured_display );
		}

		$video_values = $this->get_video_field_values( $post->ID );
		?>
		<div class="wpdino-portfolio-meta-tabs" data-wpdino-meta-tabs>
			<div class="wpdino-portfolio-meta-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Portfolio item settings', 'dinofolio' ); ?>">
				<button type="button" class="wpdino-portfolio-meta-tabs__tab is-active" role="tab" aria-selected="true" aria-controls="wpdino-portfolio-meta-panel-general" id="wpdino-portfolio-meta-tab-general" data-wpdino-meta-tab="general"><?php esc_html_e( 'General', 'dinofolio' ); ?></button>
				<button type="button" class="wpdino-portfolio-meta-tabs__tab" role="tab" aria-selected="false" aria-controls="wpdino-portfolio-meta-panel-video" id="wpdino-portfolio-meta-tab-video" data-wpdino-meta-tab="video"><?php esc_html_e( 'Video', 'dinofolio' ); ?></button>
			</div>

			<div class="wpdino-portfolio-meta-tabs__panel is-active" role="tabpanel" id="wpdino-portfolio-meta-panel-general" aria-labelledby="wpdino-portfolio-meta-tab-general" data-wpdino-meta-panel="general">
		<table class="form-table wpdino-portfolio-meta-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Featured Image', 'dinofolio' ); ?></th>
					<td><?php $this->render_toggle_field( 'featured_image_display', $values['featured_image_display'] ); ?></td>
				</tr>
				<tr class="wpdino-featured-image-size-row" <?php echo $show_featured_size_row ? '' : 'style="display:none;"'; ?>>
					<th scope="row"><label for="wpdino_featured_image_size"><?php esc_html_e( 'Featured Image Size', 'dinofolio' ); ?></label></th>
					<td>
						<select id="wpdino_featured_image_size" name="wpdino_featured_image_size">
							<?php foreach ( $this->get_ordered_image_sizes() as $size_key => $size_label ) : ?>
								<option value="<?php echo esc_attr( $size_key ); ?>" <?php selected( $values['featured_image_size'], $size_key ); ?>><?php echo esc_html( $size_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Related Projects', 'dinofolio' ); ?></th>
					<td><?php $this->render_toggle_field( 'related_projects', $values['related_projects'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpdino_related_projects_style"><?php esc_html_e( 'Related Projects Style', 'dinofolio' ); ?></label></th>
					<td>
						<?php $this->render_related_projects_style_picker( $values['related_projects_style'] ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpdino_related_projects_title"><?php esc_html_e( 'Related Projects Title', 'dinofolio' ); ?></label></th>
					<td><input type="text" class="regular-text" id="wpdino_related_projects_title" name="wpdino_related_projects_title" value="<?php echo esc_attr( $values['related_projects_title'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpdino_related_projects_number"><?php esc_html_e( 'Number of Related Projects', 'dinofolio' ); ?></label></th>
					<td><?php $this->render_related_projects_count_field( $values['related_projects_number'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Date', 'dinofolio' ); ?></th>
					<td><?php $this->render_toggle_field( 'date_display', $values['date_display'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpdino_date_label"><?php esc_html_e( 'Label of Date', 'dinofolio' ); ?></label></th>
					<td><input type="text" class="regular-text" id="wpdino_date_label" name="wpdino_date_label" value="<?php echo esc_attr( $values['date_label'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpdino_date_of_work"><?php esc_html_e( 'Date of Work', 'dinofolio' ); ?></label></th>
					<td><input type="text" class="regular-text" id="wpdino_date_of_work" name="wpdino_date_of_work" value="<?php echo esc_attr( $values['date_of_work'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpdino_external_url"><?php esc_html_e( 'External URL', 'dinofolio' ); ?></label></th>
					<td><input type="url" class="regular-text" id="wpdino_external_url" name="wpdino_external_url" value="<?php echo esc_attr( $values['external_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpdino_button_label"><?php esc_html_e( 'Label of Button', 'dinofolio' ); ?></label></th>
					<td><input type="text" class="regular-text" id="wpdino_button_label" name="wpdino_button_label" value="<?php echo esc_attr( $values['button_label'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Attributes', 'dinofolio' ); ?></th>
					<td>
						<div id="wpdino-attributes-wrapper">
							<?php
							foreach ( $values['attributes'] as $attribute ) :
								$attr_label = isset( $attribute['label'] ) ? $attribute['label'] : '';
								$attr_value = isset( $attribute['value'] ) ? $attribute['value'] : '';
								?>
								<div class="wpdino-attr-row">
									<input type="text" name="wpdino_attributes_label[]" placeholder="<?php esc_attr_e( 'Label', 'dinofolio' ); ?>" value="<?php echo esc_attr( $attr_label ); ?>">
									<input type="text" name="wpdino_attributes_value[]" placeholder="<?php esc_attr_e( 'Value', 'dinofolio' ); ?>" value="<?php echo esc_attr( $attr_value ); ?>">
									<a href="#" class="button-link-delete wpdino-remove-attr"><?php esc_html_e( 'Remove', 'dinofolio' ); ?></a>
								</div>
							<?php endforeach; ?>
						</div>
						<p><button type="button" class="button button-secondary" id="wpdino-add-attribute"><?php esc_html_e( 'Add More', 'dinofolio' ); ?></button></p>
					</td>
				</tr>
			</tbody>
		</table>

		<script>
			(function() {
				var addBtn = document.getElementById('wpdino-add-attribute');
				var wrapper = document.getElementById('wpdino-attributes-wrapper');
				if (!addBtn || !wrapper) { return; }

				addBtn.addEventListener('click', function() {
					var row = document.createElement('div');
					row.className = 'wpdino-attr-row';
					row.innerHTML = '<input type="text" name="wpdino_attributes_label[]" placeholder="<?php echo esc_attr( 'Label' ); ?>">'
						+ '<input type="text" name="wpdino_attributes_value[]" placeholder="<?php echo esc_attr( 'Value' ); ?>">'
						+ '<a href="#" class="button-link-delete wpdino-remove-attr"><?php echo esc_html( 'Remove' ); ?></a>';
					wrapper.appendChild(row);
				});

				wrapper.addEventListener('click', function(event) {
					if (!event.target.classList.contains('wpdino-remove-attr')) { return; }
					event.preventDefault();
					var row = event.target.closest('.wpdino-attr-row');
					if (row) {
						row.remove();
					}
				});
			})();
		</script>
			</div>

			<div class="wpdino-portfolio-meta-tabs__panel" role="tabpanel" id="wpdino-portfolio-meta-panel-video" aria-labelledby="wpdino-portfolio-meta-tab-video" data-wpdino-meta-panel="video" hidden>
				<?php $this->render_portfolio_video_tab( $video_values ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Video tab in the portfolio meta box.
	 *
	 * @param array $values Video field values.
	 * @return void
	 */
	private function render_portfolio_video_tab( $values ) {
		?>
		<div class="wpdino-portfolio-video-sections">
			<section class="wpdino-portfolio-video-section">
				<h3 class="wpdino-portfolio-video-section__title"><?php esc_html_e( 'Video in Lightbox', 'dinofolio' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Opens the selected video in the listing lightbox instead of the featured image. Requires the listing lightbox option to be enabled. You can also use the video thumbnail as the portfolio featured image.', 'dinofolio' ); ?></p>
				<table class="form-table wpdino-portfolio-meta-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Video in Lightbox', 'dinofolio' ); ?></th>
							<td><?php $this->render_video_on_off_field( 'video_lightbox', $values['video_lightbox'] ); ?></td>
						</tr>
					</tbody>
				</table>
				<div class="wpdino-video-fields" data-wpdino-video-fields="lightbox" <?php echo 'on' === $values['video_lightbox'] ? '' : 'hidden'; ?>>
					<?php $this->render_video_source_fields( 'video_lightbox', $values['video_lightbox_type'], $values['video_lightbox_url'], $values['video_lightbox_mp4_id'] ); ?>
				</div>
			</section>
		</div>
		<?php
	}

	/**
	 * Get stored video field values for the meta box.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function get_video_field_values( $post_id ) {
		$lightbox_mp4_id = absint( get_post_meta( $post_id, '_wpdino_video_lightbox_mp4_id', true ) );

		return array(
			'video_lightbox'           => 'on' === get_post_meta( $post_id, '_wpdino_video_lightbox', true ) ? 'on' : 'off',
			'video_lightbox_type'      => Portfolio_Video::normalize_type( get_post_meta( $post_id, '_wpdino_video_lightbox_type', true ) ),
			'video_lightbox_url'       => (string) get_post_meta( $post_id, '_wpdino_video_lightbox_url', true ),
			'video_lightbox_mp4_id'    => $lightbox_mp4_id,
			'video_lightbox_mp4_label' => $this->get_video_attachment_label( $lightbox_mp4_id ),
		);
	}

	/**
	 * Human-readable label for a selected MP4 attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	private function get_video_attachment_label( $attachment_id ) {
		$attachment_id = absint( $attachment_id );

		if ( $attachment_id < 1 ) {
			return '';
		}

		$title = get_the_title( $attachment_id );

		/* translators: %d: media attachment ID. */
		return $title ? $title : sprintf( esc_html__( 'Attachment #%d', 'dinofolio' ), $attachment_id );
	}

	/**
	 * Render ON / OFF toggle for video sections.
	 *
	 * @param string $key   Field key.
	 * @param string $value Current value.
	 * @return void
	 */
	private function render_video_on_off_field( $key, $value ) {
		?>
		<div class="wpdino-toggle-group wpdino-toggle-group--binary" data-wpdino-video-toggle="<?php echo esc_attr( $key ); ?>">
			<label><input type="radio" name="wpdino_<?php echo esc_attr( $key ); ?>" value="on" <?php checked( $value, 'on' ); ?>> <?php esc_html_e( 'On', 'dinofolio' ); ?></label>
			<label><input type="radio" name="wpdino_<?php echo esc_attr( $key ); ?>" value="off" <?php checked( $value, 'off' ); ?>> <?php esc_html_e( 'Off', 'dinofolio' ); ?></label>
		</div>
		<?php
	}

	/**
	 * Render shared video source controls.
	 *
	 * @param string $prefix Field prefix.
	 * @param string $type   Video type.
	 * @param string $url    External URL.
	 * @param int    $mp4_id MP4 attachment ID.
	 * @return void
	 */
	private function render_video_source_fields( $prefix, $type, $url, $mp4_id ) {
		$type        = Portfolio_Video::normalize_type( $type );
		$label       = $this->get_video_attachment_label( $mp4_id );
		$section_key = 'lightbox';
		?>
		<table class="form-table wpdino-portfolio-meta-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $prefix ); ?>_type"><?php esc_html_e( 'Video Type', 'dinofolio' ); ?></label></th>
					<td>
						<select id="<?php echo esc_attr( $prefix ); ?>_type" name="<?php echo esc_attr( $prefix ); ?>_type" class="wpdino-video-type-select" data-wpdino-video-type="<?php echo esc_attr( $section_key ); ?>">
							<option value="mp4" <?php selected( $type, 'mp4' ); ?>><?php esc_html_e( 'MP4 file', 'dinofolio' ); ?></option>
							<option value="youtube" <?php selected( $type, 'youtube' ); ?>><?php esc_html_e( 'YouTube', 'dinofolio' ); ?></option>
							<option value="vimeo" <?php selected( $type, 'vimeo' ); ?>><?php esc_html_e( 'Vimeo', 'dinofolio' ); ?></option>
						</select>
					</td>
				</tr>
				<tr class="wpdino-video-url-row" data-wpdino-video-url-row="<?php echo esc_attr( $section_key ); ?>" <?php echo 'mp4' === $type ? 'style="display:none;"' : ''; ?>>
					<th scope="row"><label for="<?php echo esc_attr( $prefix ); ?>_url"><?php esc_html_e( 'Video URL', 'dinofolio' ); ?></label></th>
					<td>
						<input type="url" class="regular-text code" id="<?php echo esc_attr( $prefix ); ?>_url" name="<?php echo esc_attr( $prefix ); ?>_url" value="<?php echo esc_attr( $url ); ?>" placeholder="<?php echo esc_attr( 'youtube' === $type ? 'https://www.youtube.com/watch?v=' : ( 'vimeo' === $type ? 'https://vimeo.com/' : 'https://' ) ); ?>">
						<p class="description"><?php esc_html_e( 'Paste a public YouTube or Vimeo URL.', 'dinofolio' ); ?></p>
						<?php if ( 'lightbox' === $section_key ) : ?>
						<div class="wpdino-video-thumb-preview" data-wpdino-video-thumb-preview="lightbox" hidden>
							<img alt="">
							<button type="button" class="button button-secondary wpdino-set-featured-from-video" data-wpdino-thumb-section="lightbox"><?php esc_html_e( 'Use as Featured Image', 'dinofolio' ); ?></button>
							<p class="description wpdino-video-thumb-status"></p>
						</div>
						<?php endif; ?>
					</td>
				</tr>
				<tr class="wpdino-video-mp4-row" data-wpdino-video-mp4-row="<?php echo esc_attr( $section_key ); ?>" <?php echo 'mp4' !== $type ? 'style="display:none;"' : ''; ?>>
					<th scope="row"><?php esc_html_e( 'MP4 File', 'dinofolio' ); ?></th>
					<td>
						<div class="wpdino-video-mp4-control" data-wpdino-video-mp4="<?php echo esc_attr( $section_key ); ?>">
							<input type="hidden" id="<?php echo esc_attr( $prefix ); ?>_mp4_id" name="<?php echo esc_attr( $prefix ); ?>_mp4_id" value="<?php echo esc_attr( $mp4_id ); ?>">
							<span class="wpdino-video-mp4-label" data-wpdino-video-mp4-label="<?php echo esc_attr( $section_key ); ?>" <?php echo $label ? '' : 'hidden'; ?>><?php echo esc_html( $label ); ?></span>
							<button type="button" class="button button-secondary wpdino-video-mp4-select" data-wpdino-video-mp4-select="<?php echo esc_attr( $section_key ); ?>"><?php esc_html_e( 'Select MP4', 'dinofolio' ); ?></button>
							<button type="button" class="button-link-delete wpdino-video-mp4-remove" data-wpdino-video-mp4-remove="<?php echo esc_attr( $section_key ); ?>" <?php echo $mp4_id ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'dinofolio' ); ?></button>
						</div>
						<p class="description"><?php esc_html_e( 'Upload or choose an MP4 video from the Media Library.', 'dinofolio' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render gallery metabox for gallery format portfolios.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_portfolio_gallery_meta_box( $post ) {
		$image_ids             = $this->get_gallery_image_ids( $post->ID );
		$gallery_display_style = $this->normalize_gallery_display_style(
			get_post_meta( $post->ID, '_wpdino_gallery_display_style', true )
		);
		?>
		<div class="wpdino-gallery-metabox">
			<div class="wpdino-gallery-display-style">
				<label for="wpdino_gallery_display_style"><?php esc_html_e( 'Gallery Display', 'dinofolio' ); ?></label>
				<div class="wpdino-format-group">
					<?php foreach ( $this->get_gallery_display_style_options() as $option_value => $option_label ) : ?>
						<label>
							<input
								type="radio"
								name="wpdino_gallery_display_style"
								value="<?php echo esc_attr( $option_value ); ?>"
								<?php checked( $gallery_display_style, $option_value ); ?>
							/>
							<?php echo esc_html( $option_label ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
			<p class="description"><?php esc_html_e( 'Upload multiple images and drag them to reorder.', 'dinofolio' ); ?></p>
			<ul id="wpdino-gallery-images" class="wpdino-gallery-images">
				<?php foreach ( $image_ids as $image_id ) : ?>
					<?php
					$thumbnail_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
					if ( ! $thumbnail_url ) {
						continue;
					}
					?>
					<li class="wpdino-gallery-item" data-id="<?php echo esc_attr( $image_id ); ?>">
						<span class="wpdino-gallery-drag" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'dinofolio' ); ?>"></span>
						<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="" />
						<button type="button" class="wpdino-gallery-remove" aria-label="<?php esc_attr_e( 'Remove image', 'dinofolio' ); ?>">&times;</button>
						<input type="hidden" name="wpdino_gallery_images[]" value="<?php echo esc_attr( $image_id ); ?>" />
					</li>
				<?php endforeach; ?>
			</ul>
			<p>
				<button type="button" class="button button-secondary" id="wpdino-gallery-add"><?php esc_html_e( 'Add Images', 'dinofolio' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Save metabox values.
	 *
	 * @param int $post_id Current post ID.
	 * @return void
	 */
	public function save_portfolio_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['wpdino_portfolio_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpdino_portfolio_meta_nonce'] ) ), 'wpdino_portfolio_meta_box' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$toggle_fields = array( 'featured_image_display', 'related_projects', 'date_display' );
		foreach ( $toggle_fields as $field ) {
			$raw_value = isset( $_POST[ 'wpdino_' . $field ] ) ? sanitize_key( wp_unslash( $_POST[ 'wpdino_' . $field ] ) ) : 'default';
			$value     = in_array( $raw_value, array( 'on', 'off', 'default' ), true ) ? $raw_value : 'default';
			update_post_meta( $post_id, '_wpdino_' . $field, $value );
		}

		$available_image_sizes = array_keys( $this->get_ordered_image_sizes() );
		$featured_image_size   = isset( $_POST['wpdino_featured_image_size'] ) ? sanitize_key( wp_unslash( $_POST['wpdino_featured_image_size'] ) ) : 'dinofolio-featured-1200x900';
		if ( ! in_array( $featured_image_size, $available_image_sizes, true ) ) {
			$featured_image_size = 'dinofolio-featured-1200x900';
		}
		update_post_meta( $post_id, '_wpdino_featured_image_size', $featured_image_size );

		$related_projects_style = isset( $_POST['wpdino_related_projects_style'] )
			? $this->normalize_related_projects_style( sanitize_key( wp_unslash( $_POST['wpdino_related_projects_style'] ) ) )
			: 'grid';
		update_post_meta( $post_id, '_wpdino_related_projects_style', $related_projects_style );
		update_post_meta( $post_id, '_wpdino_related_projects_title', isset( $_POST['wpdino_related_projects_title'] ) ? sanitize_text_field( wp_unslash( $_POST['wpdino_related_projects_title'] ) ) : '' );
		if ( isset( $_POST['wpdino_related_projects_number'] ) ) {
			$related_projects_number = $this->clamp_related_projects_count( absint( wp_unslash( $_POST['wpdino_related_projects_number'] ) ) );
			update_post_meta( $post_id, '_wpdino_related_projects_number', $related_projects_number );
			delete_post_meta( $post_id, '_wpdino_related_works_number' );
		}
		update_post_meta( $post_id, '_wpdino_date_label', isset( $_POST['wpdino_date_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wpdino_date_label'] ) ) : '' );
		update_post_meta( $post_id, '_wpdino_date_of_work', isset( $_POST['wpdino_date_of_work'] ) ? sanitize_text_field( wp_unslash( $_POST['wpdino_date_of_work'] ) ) : '' );
		update_post_meta( $post_id, '_wpdino_external_url', isset( $_POST['wpdino_external_url'] ) ? esc_url_raw( wp_unslash( $_POST['wpdino_external_url'] ) ) : '' );
		update_post_meta( $post_id, '_wpdino_button_label', isset( $_POST['wpdino_button_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wpdino_button_label'] ) ) : '' );

		$attributes       = array();
		$labels           = isset( $_POST['wpdino_attributes_label'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['wpdino_attributes_label'] ) ) : array();
		$attribute_values = isset( $_POST['wpdino_attributes_value'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['wpdino_attributes_value'] ) ) : array();
		$count            = max( count( $labels ), count( $attribute_values ) );

		for ( $i = 0; $i < $count; $i++ ) {
			$label = isset( $labels[ $i ] ) ? $labels[ $i ] : '';
			$value = isset( $attribute_values[ $i ] ) ? $attribute_values[ $i ] : '';
			if ( '' === $label && '' === $value ) {
				continue;
			}
			$attributes[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		update_post_meta( $post_id, '_wpdino_attributes', $attributes );
		$this->migrate_legacy_portfolio_format_meta( $post_id );

		$gallery_image_ids = array();
		if ( isset( $_POST['wpdino_gallery_images'] ) && is_array( $_POST['wpdino_gallery_images'] ) ) {
			$gallery_image_ids = $this->sanitize_gallery_image_ids(
				array_map( 'absint', wp_unslash( $_POST['wpdino_gallery_images'] ) )
			);
		}
		update_post_meta( $post_id, '_wpdino_gallery_images', $gallery_image_ids );

		$gallery_display_style = isset( $_POST['wpdino_gallery_display_style'] )
			? $this->normalize_gallery_display_style( sanitize_key( wp_unslash( $_POST['wpdino_gallery_display_style'] ) ) )
			: 'grid';
		update_post_meta( $post_id, '_wpdino_gallery_display_style', $gallery_display_style );

		$video_lightbox_type = isset( $_POST['video_lightbox_type'] )
			? Portfolio_Video::normalize_type( sanitize_key( wp_unslash( $_POST['video_lightbox_type'] ) ) )
			: 'mp4';

		$this->save_video_meta_fields(
			$post_id,
			array(
				'enabled' => isset( $_POST['wpdino_video_lightbox'] ) && 'on' === sanitize_key( wp_unslash( $_POST['wpdino_video_lightbox'] ) ) ? 'on' : 'off',
				'type'    => $video_lightbox_type,
				'url'     => isset( $_POST['video_lightbox_url'] ) ? esc_url_raw( wp_unslash( $_POST['video_lightbox_url'] ) ) : '',
				'mp4_id'  => isset( $_POST['video_lightbox_mp4_id'] )
					? Portfolio_Video::sanitize_mp4_attachment_id( absint( wp_unslash( $_POST['video_lightbox_mp4_id'] ) ) )
					: 0,
			)
		);
	}

	/**
	 * Save video tab fields.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $fields  Sanitized video field values.
	 * @return void
	 */
	private function save_video_meta_fields( $post_id, $fields ) {
		$type = isset( $fields['type'] ) ? Portfolio_Video::normalize_type( $fields['type'] ) : 'mp4';

		update_post_meta( $post_id, '_wpdino_video_lightbox', ! empty( $fields['enabled'] ) && 'on' === $fields['enabled'] ? 'on' : 'off' );
		update_post_meta( $post_id, '_wpdino_video_lightbox_type', $type );
		update_post_meta( $post_id, '_wpdino_video_lightbox_url', Portfolio_Video::sanitize_video_url( $type, isset( $fields['url'] ) ? $fields['url'] : '' ) );
		update_post_meta( $post_id, '_wpdino_video_lightbox_mp4_id', isset( $fields['mp4_id'] ) ? absint( $fields['mp4_id'] ) : 0 );
	}

	/**
	 * Append metabox data output to single portfolio content.
	 *
	 * @param string $content The post content.
	 * @return string
	 */
	public function append_single_portfolio_meta_content( $content ) {
		if ( ! is_singular( 'wpdino_portfolio' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $content;
		}

		$date_display = $this->get_effective_meta(
			$post_id,
			'date_display',
			'portfolio_meta_default_date_display',
			'on',
			array( 'default' )
		);
		$date_label = $this->get_effective_meta(
			$post_id,
			'date_label',
			'portfolio_meta_default_date_label',
			esc_html__( 'Date', 'dinofolio' ),
			array()
		);
		$date_of_work = $this->get_effective_meta(
			$post_id,
			'date_of_work',
			'portfolio_meta_default_date_of_work',
			'',
			array()
		);

		$external_url = $this->get_effective_meta(
			$post_id,
			'external_url',
			'portfolio_meta_default_external_url',
			'',
			array()
		);
		$button_label = $this->get_effective_meta(
			$post_id,
			'button_label',
			'portfolio_meta_default_button_label',
			esc_html__( 'Launch', 'dinofolio' ),
			array()
		);
		$attributes = $this->get_meta( $post_id, 'attributes', $this->get_default_attributes_from_settings() );

		$related_project = $this->get_effective_meta(
			$post_id,
			'related_projects',
			'portfolio_meta_default_related_projects',
			'on',
			array( 'default' )
		);
		$featured_image_display = $this->get_effective_meta(
			$post_id,
			'featured_image_display',
			'portfolio_meta_default_featured_image_display',
			'on',
			array( 'default' )
		);
		$featured_image_size = $this->get_effective_meta(
			$post_id,
			'featured_image_size',
			'portfolio_meta_default_featured_image_size',
			'dinofolio-featured-1200x900',
			array()
		);
		$available_image_sizes = array_keys( $this->get_ordered_image_sizes() );
		if ( ! in_array( $featured_image_size, $available_image_sizes, true ) ) {
			$featured_image_size = 'dinofolio-featured-1200x900';
		}
		$related_projects_style = $this->normalize_related_projects_style(
			$this->get_effective_meta(
				$post_id,
				'related_projects_style',
				'portfolio_meta_default_related_projects_style',
				'grid',
				array()
			)
		);
		$related_projects_title = $this->get_effective_meta(
			$post_id,
			'related_projects_title',
			'portfolio_meta_default_related_projects_title',
			esc_html__( 'Related Projects', 'dinofolio' ),
			array()
		);
		$related_projects_count = $this->get_related_projects_count( $post_id );
		$portfolio_format = $this->get_portfolio_post_format( $post_id );
		$gallery_images   = array();

		$gallery_display_style = 'grid';
		if ( $this->is_gallery_format( $post_id ) ) {
			$gallery_display_style = $this->normalize_gallery_display_style(
				get_post_meta( $post_id, '_wpdino_gallery_display_style', true )
			);
			$gallery_images = $this->get_gallery_images_data(
				$post_id,
				$this->get_gallery_display_image_size( $gallery_display_style, $featured_image_size )
			);
		}

		$clean_attributes = array();
		if ( is_array( $attributes ) ) {
			foreach ( $attributes as $attribute ) {
				$label = isset( $attribute['label'] ) ? $attribute['label'] : '';
				$value = isset( $attribute['value'] ) ? $attribute['value'] : '';
				if ( '' === $label && '' === $value ) {
					continue;
				}
				$clean_attributes[] = array(
					'label' => $label,
					'value' => $value,
				);
			}
		}

		$show_featured_image = ( 'off' !== $featured_image_display );
		if ( $this->is_gallery_format( $post_id ) && ! empty( $gallery_images ) ) {
			$show_featured_image = false;
		}

		$template_data = array(
			'date_display'        => $date_display,
			'date_label'          => $date_label,
			'date_value'          => $date_of_work ? $date_of_work : get_the_date( '', $post_id ),
			'portfolio_format'        => $portfolio_format,
			'gallery_images'          => $gallery_images,
			'gallery_display_style'   => $gallery_display_style,
			'featured_image_id'   => get_post_thumbnail_id( $post_id ),
			'featured_image_size' => $featured_image_size,
			'featured_image_url'  => $show_featured_image ? get_the_post_thumbnail_url( $post_id, $featured_image_size ) : '',
			'external_url'        => $external_url,
			'button_label'        => $button_label ? $button_label : __( 'Launch', 'dinofolio' ),
			'attributes'          => $clean_attributes,
			'permalink'           => get_permalink( $post_id ),
			'post_title'          => get_the_title( $post_id ),
			'related_projects'        => ( 'off' === $related_project ) ? array() : $this->get_related_posts_data(
				$post_id,
				( 'carousel' === $related_projects_style ) ? -1 : $related_projects_count
			),
			'related_projects_title'  => $related_projects_title ? $related_projects_title : __( 'Related Projects', 'dinofolio' ),
			'related_projects_style'  => $related_projects_style,
			'related_projects_count'  => $related_projects_count,
		);

		$rendered = $this->render_single_meta_template( $template_data );

		return $content . $rendered;
	}

	/**
	 * Enqueue frontend single portfolio assets.
	 *
	 * @return void
	 */
	public function enqueue_single_portfolio_assets() {
		if ( ! is_singular( 'wpdino_portfolio' ) ) {
			return;
		}

		wp_enqueue_style(
			'dinofolio-single-portfolio-meta',
			DINOFOLIO_URL . 'assets/css/single-portfolio-meta.css',
			array(),
			DINOFOLIO_VERSION
		);

		$post_id          = get_queried_object_id();
		$gallery_images        = array();
		$gallery_display_style = 'grid';
		if ( $post_id && $this->is_gallery_format( $post_id ) ) {
			$gallery_images        = $this->get_gallery_image_ids( $post_id );
			$gallery_display_style = $this->normalize_gallery_display_style(
				get_post_meta( $post_id, '_wpdino_gallery_display_style', true )
			);
		}
		$needs_script = false;

		if ( ! empty( $gallery_images ) ) {
			$this->enqueue_single_gallery_lightbox_assets();
			$needs_script = true;
		}

		if ( ! empty( $gallery_images ) && 'slider' === $gallery_display_style ) {
			$needs_script = true;
		}

		if ( $post_id && 'carousel' === $this->normalize_related_projects_style(
			$this->get_effective_meta(
				$post_id,
				'related_projects_style',
				'portfolio_meta_default_related_projects_style',
				'grid',
				array()
			)
		) ) {
			$needs_script = true;
		}

		if ( $needs_script ) {
			$script_deps = array();
			if ( ! empty( $gallery_images ) && class_exists( '\WPDINO_Portfolio_Display' ) ) {
				$script_deps[] = \WPDINO_Portfolio_Display::get_glightbox_script_handle();
			}

			wp_enqueue_script(
				'dinofolio-single-portfolio-meta',
				DINOFOLIO_URL . 'assets/js/single-portfolio-meta.js',
				$script_deps,
				DINOFOLIO_VERSION,
				true
			);
		}
	}

	/**
	 * Enqueue admin assets for portfolio metabox fields.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_portfolio_meta_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'wpdino_portfolio' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'dinofolio-portfolio-meta-admin',
			DINOFOLIO_URL . 'includes/admin/assets/css/admin-portfolio-meta.css',
			array(),
			DINOFOLIO_VERSION
		);

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script(
			'dinofolio-portfolio-meta-admin',
			DINOFOLIO_URL . 'includes/admin/assets/js/portfolio-meta.js',
			array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'media-editor' ),
			DINOFOLIO_VERSION,
			true
		);

		$video_admin_deps = array(
			'jquery',
			'dinofolio-portfolio-meta-admin',
		);

		if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( 'wpdino_portfolio' ) ) {
			$video_admin_deps[] = 'wp-edit-post';
		}

		wp_enqueue_script(
			'dinofolio-portfolio-video-admin',
			DINOFOLIO_URL . 'includes/admin/assets/js/portfolio-video-admin.js',
			$video_admin_deps,
			DINOFOLIO_VERSION,
			true
		);

		wp_localize_script(
			'dinofolio-portfolio-video-admin',
			'wpdinoPortfolioVideo',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'dinofolio_portfolio_video_admin' ),
				'i18n'    => array(
					'useFeaturedImage' => esc_html__( 'Use as Featured Image', 'dinofolio' ),
					'featuredImageSet' => esc_html__( 'This is the Featured Image', 'dinofolio' ),
					'thumbError'       => esc_html__( 'Unable to set featured image from video.', 'dinofolio' ),
				),
			)
		);

		wp_localize_script(
			'dinofolio-portfolio-meta-admin',
			'wpdinoPortfolioMeta',
			array(
				'i18n' => array(
					'addImages'      => esc_html__( 'Add Images', 'dinofolio' ),
					'selectImages'   => esc_html__( 'Select Gallery Images', 'dinofolio' ),
					'insertImages'   => esc_html__( 'Add to Gallery', 'dinofolio' ),
					'removeImage'    => esc_html__( 'Remove image', 'dinofolio' ),
					'dragToReorder'  => esc_html__( 'Drag to reorder', 'dinofolio' ),
					'selectVideo'    => esc_html__( 'Select MP4 Video', 'dinofolio' ),
					'useVideo'       => esc_html__( 'Use Video', 'dinofolio' ),
				),
			)
		);
	}

	/**
	 * Render single meta using a theme-overridable template.
	 *
	 * @param array $template_data Data for template.
	 * @return string
	 */
	private function render_single_meta_template( $template_data ) {
		$theme_template = locate_template( 'dinofolio/single-portfolio-meta.php' );
		$template_path  = $theme_template ? $theme_template : DINOFOLIO_PATH . 'templates/single-portfolio-meta.php';

		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		$dinofolio_data = $template_data;
		ob_start();
		include $template_path;

		return (string) ob_get_clean();
	}

	/**
	 * Get related posts data array by categories.
	 *
	 * @param int $post_id Current post ID.
	 * @param int $count Number of related posts (-1 for all matches).
	 * @return array
	 */
	private function get_related_posts_data( $post_id, $count ) {
		$count = (int) $count;
		if ( $count < 1 ) {
			$count = -1;
		}
		$term_ids = wp_get_post_terms( $post_id, 'wpdino_portfolio_category', array( 'fields' => 'ids' ) );

		if ( empty( $term_ids ) || is_wp_error( $term_ids ) ) {
			return array();
		}

		return $this->query_related_portfolio_posts(
			array(
				'posts_per_page' => $count,
				'post__not_in'   => array( (int) $post_id ),
				'tax_query'      => array(
					array(
						'taxonomy' => 'wpdino_portfolio_category',
						'field'    => 'term_id',
						'terms'    => $term_ids,
					),
				),
			)
		);
	}

	/**
	 * Run a portfolio query for related items.
	 *
	 * @param array $query_args WP_Query arguments.
	 * @return array
	 */
	private function query_related_portfolio_posts( $query_args ) {
		$query = new \WP_Query(
			wp_parse_args(
				$query_args,
				array(
					'post_type'           => 'wpdino_portfolio',
					'post_status'         => 'publish',
					'orderby'             => 'date',
					'order'               => 'DESC',
					'ignore_sticky_posts' => true,
					'suppress_filters'    => true,
					'no_found_rows'       => true,
				)
			)
		);

		if ( ! $query->have_posts() ) {
			return array();
		}

		$posts = array();
		while ( $query->have_posts() ) {
			$query->the_post();
			$related_id   = get_the_ID();
			$thumbnail_id = class_exists( '\DinoFolio\Util' )
				? \DinoFolio\Util::get_portfolio_preview_image_id( $related_id )
				: (int) get_post_thumbnail_id( $related_id );
			$posts[]      = array(
				'title'         => get_the_title(),
				'url'           => get_permalink(),
				'thumbnail_id'  => $thumbnail_id,
				'thumbnail_url' => $thumbnail_id ? (string) wp_get_attachment_image_url( $thumbnail_id, 'dinofolio-related-card' ) : '',
			);
		}
		wp_reset_postdata();

		return $posts;
	}

	/**
	 * Resolve how many related projects should be shown for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	private function get_related_projects_count( $post_id ) {
		$count = 0;

		if ( metadata_exists( 'post', $post_id, '_wpdino_related_projects_number' ) ) {
			$count = (int) get_post_meta( $post_id, '_wpdino_related_projects_number', true );
		} elseif ( metadata_exists( 'post', $post_id, '_wpdino_related_works_number' ) ) {
			$count = (int) get_post_meta( $post_id, '_wpdino_related_works_number', true );
		}

		if ( $count < 1 ) {
			$count = (int) $this->get_settings_default( 'portfolio_meta_default_related_projects_number', 3 );
		}

		return $this->clamp_related_projects_count( $count );
	}

	/**
	 * Clamp related projects count to allowed slider range.
	 *
	 * @param int $count Raw count.
	 * @return int
	 */
	private function clamp_related_projects_count( $count ) {
		return max( self::RELATED_PROJECTS_COUNT_MIN, min( self::RELATED_PROJECTS_COUNT_MAX, (int) $count ) );
	}

	/**
	 * Render related projects count range control.
	 *
	 * @param int $value Current value.
	 * @return void
	 */
	private function render_related_projects_count_field( $value ) {
		$value = $this->clamp_related_projects_count( $value );
		?>
		<div class="wpdino-range-control wpdino-related-projects-count-control">
			<input
				type="range"
				class="wpdino-related-projects-range"
				id="wpdino_related_projects_number"
				name="wpdino_related_projects_number"
				min="<?php echo esc_attr( self::RELATED_PROJECTS_COUNT_MIN ); ?>"
				max="<?php echo esc_attr( self::RELATED_PROJECTS_COUNT_MAX ); ?>"
				step="1"
				value="<?php echo esc_attr( $value ); ?>"
			/>
			<span class="wpdino-range-value" id="wpdino_related_projects_number_value"><?php echo esc_html( $value ); ?></span>
		</div>
		<?php
	}

	/**
	 * Render ON / DEFAULT / OFF toggle radios.
	 *
	 * @param string $key Field key.
	 * @param string $value Field value.
	 * @return void
	 */
	/**
	 * Gallery display style options for admin UI.
	 *
	 * @return array
	 */
	private function get_gallery_display_style_options() {
		return array(
			'grid'   => esc_html__( 'Grid', 'dinofolio' ),
			'slider' => esc_html__( 'Slider', 'dinofolio' ),
		);
	}

	/**
	 * Normalize gallery display style values.
	 *
	 * @param string $style Raw style value.
	 * @return string
	 */
	private function normalize_gallery_display_style( $style ) {
		$style = sanitize_key( (string) $style );

		return in_array( $style, array( 'grid', 'slider' ), true ) ? $style : 'grid';
	}

	/**
	 * Resolve the portfolio post format, with legacy meta fallback.
	 *
	 * @param int $post_id Post ID.
	 * @return string Either "standard" or "gallery".
	 */
	private function get_portfolio_post_format( $post_id ) {
		$format = get_post_format( $post_id );

		if ( 'gallery' === $format ) {
			return 'gallery';
		}

		$legacy_format = get_post_meta( $post_id, '_wpdino_portfolio_format', true );
		if ( 'gallery' === $legacy_format ) {
			return 'gallery';
		}

		return 'standard';
	}

	/**
	 * Whether a portfolio item uses the gallery post format.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function is_gallery_format( $post_id ) {
		return 'gallery' === $this->get_portfolio_post_format( $post_id );
	}

	/**
	 * Migrate legacy custom format meta to the native post format taxonomy.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function migrate_legacy_portfolio_format_meta( $post_id ) {
		if ( ! metadata_exists( 'post', $post_id, '_wpdino_portfolio_format' ) ) {
			return;
		}

		$legacy_format = get_post_meta( $post_id, '_wpdino_portfolio_format', true );

		if ( 'gallery' === $legacy_format && 'gallery' !== get_post_format( $post_id ) ) {
			set_post_format( $post_id, 'gallery' );
		}

		delete_post_meta( $post_id, '_wpdino_portfolio_format' );
	}

	/**
	 * Get stored gallery attachment IDs for a portfolio post.
	 *
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	private function get_gallery_image_ids( $post_id ) {
		$stored = get_post_meta( $post_id, '_wpdino_gallery_images', true );

		if ( ! is_array( $stored ) ) {
			return array();
		}

		return $this->sanitize_gallery_image_ids( $stored );
	}

	/**
	 * Sanitize and validate gallery attachment IDs while preserving order.
	 *
	 * @param array $image_ids Raw image IDs.
	 * @return int[]
	 */
	private function sanitize_gallery_image_ids( $image_ids ) {
		$sanitized = array();

		foreach ( (array) $image_ids as $image_id ) {
			$image_id = absint( $image_id );
			if ( $image_id < 1 || ! wp_attachment_is_image( $image_id ) ) {
				continue;
			}
			if ( in_array( $image_id, $sanitized, true ) ) {
				continue;
			}
			$sanitized[] = $image_id;
		}

		return $sanitized;
	}

	/**
	 * Resolve the image size used for gallery output.
	 *
	 * @param string $display_style Gallery display style.
	 * @param string $featured_image_size Featured image size for grid layouts.
	 * @return string
	 */
	private function get_gallery_display_image_size( $display_style, $featured_image_size ) {
		if ( 'slider' === $this->normalize_gallery_display_style( $display_style ) ) {
			return 'dinofolio-gallery-slider';
		}

		return $featured_image_size;
	}

	/**
	 * Build gallery image data for frontend templates.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $image_size Registered image size.
	 * @return array
	 */
	private function get_gallery_images_data( $post_id, $image_size ) {
		$images = array();

		foreach ( $this->get_gallery_image_ids( $post_id ) as $image_id ) {
			$display_url = wp_get_attachment_image_url( $image_id, $image_size );
			$full_url    = wp_get_attachment_image_url( $image_id, 'full' );

			if ( ! $display_url || ! $full_url ) {
				continue;
			}

			$images[] = array(
				'id'          => $image_id,
				'url'         => $display_url,
				'full_url'    => $full_url,
				'alt'         => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
				'image_size'  => $image_size,
			);
		}

		return $images;
	}

	/**
	 * Enqueue GLightbox assets for single portfolio galleries.
	 *
	 * @return void
	 */
	private function enqueue_single_gallery_lightbox_assets() {
		if ( ! class_exists( '\WPDINO_Portfolio_Display' ) ) {
			return;
		}

		$display = \WPDINO_Portfolio_Display::get_instance();

		if ( ! wp_style_is( \WPDINO_Portfolio_Display::get_glightbox_style_handle(), 'registered' ) ) {
			$display->register_listing_assets();
		}

		wp_enqueue_style( \WPDINO_Portfolio_Display::get_glightbox_style_handle() );
		wp_enqueue_script( \WPDINO_Portfolio_Display::get_glightbox_script_handle() );
	}

	/**
	 * Render ON / DEFAULT / OFF toggle radios.
	 *
	 * @param string $key Field key.
	 * @param string $value Field value.
	 * @return void
	 */
	private function render_toggle_field( $key, $value ) {
		?>
		<div class="wpdino-toggle-group">
			<label><input type="radio" name="wpdino_<?php echo esc_attr( $key ); ?>" value="on" <?php checked( $value, 'on' ); ?>> <?php esc_html_e( 'On', 'dinofolio' ); ?></label>
			<label><input type="radio" name="wpdino_<?php echo esc_attr( $key ); ?>" value="default" <?php checked( $value, 'default' ); ?>> <?php esc_html_e( 'Default', 'dinofolio' ); ?></label>
			<label><input type="radio" name="wpdino_<?php echo esc_attr( $key ); ?>" value="off" <?php checked( $value, 'off' ); ?>> <?php esc_html_e( 'Off', 'dinofolio' ); ?></label>
		</div>
		<?php
	}

	/**
	 * Render image picker for related projects style.
	 *
	 * @param string $value Current value.
	 * @return void
	 */
	private function render_related_projects_style_picker( $value ) {
		$value   = $this->normalize_related_projects_style( $value );
		$options = $this->get_related_projects_style_options();
		?>
		<div class="wpdino-image-select-group wpdino-image-select-group--compact">
			<?php foreach ( $options as $option_value => $option_data ) : ?>
				<label class="wpdino-image-select-option">
					<input type="radio" name="wpdino_related_projects_style" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( $value, $option_value ); ?> />
					<img src="<?php echo esc_url( $option_data['image'] ); ?>" alt="<?php echo esc_attr( $option_data['label'] ); ?>" />
					<span class="wpdino-image-select-label"><?php echo esc_html( $option_data['label'] ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Related projects layout options for admin pickers.
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
	 * Normalize stored layout values to grid or carousel.
	 *
	 * @param string $style Raw style value.
	 * @return string
	 */
	private function normalize_related_projects_style( $style ) {
		$style = sanitize_key( (string) $style );

		$legacy_map = array(
			'style-1' => 'grid',
			'style-2' => 'carousel',
			'style-3' => 'carousel',
			'none'    => 'grid',
		);

		if ( isset( $legacy_map[ $style ] ) ) {
			return $legacy_map[ $style ];
		}

		return in_array( $style, array( 'grid', 'carousel' ), true ) ? $style : 'grid';
	}

	/**
	 * Get a metabox value with fallback.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key suffix without prefix.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private function get_meta( $post_id, $key, $default = '' ) {
		$value = $this->get_stored_meta( $post_id, $key );
		return '' === $value ? $default : $value;
	}

	/**
	 * Read post meta with backward compatibility for renamed related projects keys.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key suffix without prefix.
	 * @return mixed
	 */
	private function get_stored_meta( $post_id, $key ) {
		$meta_key = '_wpdino_' . $key;

		if ( metadata_exists( 'post', $post_id, $meta_key ) ) {
			return get_post_meta( $post_id, $meta_key, true );
		}

		$legacy_keys = array(
			'related_projects_style'  => 'related_works',
			'related_projects_title'  => 'related_works_title',
			'related_projects_number' => 'related_works_number',
		);

		if ( ! isset( $legacy_keys[ $key ] ) ) {
			return '';
		}

		$legacy_meta_key = '_wpdino_' . $legacy_keys[ $key ];

		if ( metadata_exists( 'post', $post_id, $legacy_meta_key ) ) {
			return get_post_meta( $post_id, $legacy_meta_key, true );
		}

		return '';
	}

	/**
	 * Get a value from plugin settings defaults.
	 *
	 * @param string $settings_key Settings key.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	private function get_settings_default( $settings_key, $fallback = '' ) {
		if ( ! class_exists( '\DinoFolio\DinoFolio_Settings' ) ) {
			return $fallback;
		}

		$settings = DinoFolio_Settings::instance();
		if ( ! $settings || ! method_exists( $settings, 'get_setting' ) ) {
			return $fallback;
		}

		$value = $settings->get_setting( $settings_key, null );

		if ( null === $value || '' === $value ) {
			$legacy_settings_keys = array(
				'portfolio_meta_default_related_projects_style'  => 'portfolio_meta_default_related_works',
				'portfolio_meta_default_related_projects_title'  => 'portfolio_meta_default_related_works_title',
				'portfolio_meta_default_related_projects_number' => 'portfolio_meta_default_related_works_number',
			);

			if ( isset( $legacy_settings_keys[ $settings_key ] ) ) {
				$legacy_value = $settings->get_setting( $legacy_settings_keys[ $settings_key ], null );
				if ( null !== $legacy_value && '' !== $legacy_value ) {
					return $legacy_value;
				}
			}

			return $fallback;
		}

		return $value;
	}

	/**
	 * Return post meta value with settings fallback.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key without prefix.
	 * @param string $settings_key Settings key for fallback.
	 * @param mixed  $fallback Fallback value.
	 * @param array  $extra_fallback_values Additional values that should fallback.
	 * @return mixed
	 */
	private function get_effective_meta( $post_id, $meta_key, $settings_key, $fallback = '', $extra_fallback_values = array() ) {
		$value = $this->get_stored_meta( $post_id, $meta_key );
		$fallback_values = array_merge( array( '' ), $extra_fallback_values );

		if ( in_array( $value, $fallback_values, true ) ) {
			return $this->get_settings_default( $settings_key, $fallback );
		}

		return $value;
	}

	/**
	 * Get default attributes from settings textarea.
	 *
	 * @return array
	 */
	private function get_default_attributes_from_settings() {
		$raw = $this->get_settings_default( 'portfolio_meta_default_attributes', '' );
		if ( empty( $raw ) || ! is_string( $raw ) ) {
			return array();
		}

		$items = array();
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		foreach ( (array) $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$parts = explode( '|', $line, 2 );
			$label = isset( $parts[0] ) ? sanitize_text_field( trim( $parts[0] ) ) : '';
			$value = isset( $parts[1] ) ? sanitize_text_field( trim( $parts[1] ) ) : '';

			if ( '' === $label && '' === $value ) {
				continue;
			}

			$items[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		return $items;
	}

	/**
	 * Return registered image sizes with WordPress core sizes first.
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

			$label = ucwords( str_replace( array( '-', '_' ), ' ', $size ) );
			$width = 0;
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
}

Portfolio_Meta_Boxes::instance();
