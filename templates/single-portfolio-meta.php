<?php
/**
 * Single portfolio meta template.
 *
 * Override in theme: dinofolio/single-portfolio-meta.php
 *
 * @package DinoFolio
 * @since 1.0.0
 *
 * @var array $dinofolio_data Template data from DinoFolio.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $dinofolio_data ) || ! is_array( $dinofolio_data ) ) {
	return;
}

$dinofolio_related_style   = ! empty( $dinofolio_data['related_projects_style'] ) ? $dinofolio_data['related_projects_style'] : 'grid';
$dinofolio_is_carousel     = ( 'carousel' === $dinofolio_related_style );
$dinofolio_related_columns = isset( $dinofolio_data['related_projects_count'] ) ? max( 2, min( 5, (int) $dinofolio_data['related_projects_count'] ) ) : 3;
$dinofolio_gallery_images  = ! empty( $dinofolio_data['gallery_images'] ) && is_array( $dinofolio_data['gallery_images'] ) ? $dinofolio_data['gallery_images'] : array();
$dinofolio_gallery_group   = 'dinofolio-single-gallery-' . (int) get_the_ID();
$dinofolio_gallery_style   = ! empty( $dinofolio_data['gallery_display_style'] ) ? $dinofolio_data['gallery_display_style'] : 'grid';
$dinofolio_gallery_slider  = ( 'slider' === $dinofolio_gallery_style );

?>
<div class="dinofolio-single-meta">
	<?php if ( ! empty( $dinofolio_gallery_images ) ) : ?>
		<div
			class="dinofolio-portfolio-gallery is-<?php echo esc_attr( $dinofolio_gallery_style ); ?>"
			data-dinofolio-gallery="<?php echo esc_attr( $dinofolio_gallery_group ); ?>"
		>
			<?php if ( $dinofolio_gallery_slider ) : ?>
				<div class="dinofolio-gallery-carousel-shell">
					<div class="dinofolio-gallery-carousel" data-dinofolio-gallery-carousel>
						<button type="button" class="dinofolio-carousel-nav dinofolio-carousel-prev" aria-label="<?php esc_attr_e( 'Previous image', 'dinofolio' ); ?>">
							<svg class="dinofolio-carousel-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
								<path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</button>
						<div class="dinofolio-gallery-carousel-viewport">
							<div class="dinofolio-gallery-carousel-track">
								<?php foreach ( $dinofolio_gallery_images as $dinofolio_gallery_image ) : ?>
									<?php
									$dinofolio_gallery_slide_class = 'dinofolio-gallery-slide';
									$dinofolio_gallery_image_sizes = '100vw';
									require DINOFOLIO_PATH . 'templates/parts/gallery-image.php';
									?>
								<?php endforeach; ?>
							</div>
						</div>
						<button type="button" class="dinofolio-carousel-nav dinofolio-carousel-next" aria-label="<?php esc_attr_e( 'Next image', 'dinofolio' ); ?>">
							<svg class="dinofolio-carousel-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
								<path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</button>
					</div>
				</div>
			<?php else : ?>
				<?php foreach ( $dinofolio_gallery_images as $dinofolio_gallery_image ) : ?>
					<?php
					$dinofolio_gallery_slide_class = '';
					unset( $dinofolio_gallery_image_sizes );
					require DINOFOLIO_PATH . 'templates/parts/gallery-image.php';
					?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	<?php elseif ( ! empty( $dinofolio_data['featured_image_url'] ) ) : ?>
		<div class="dinofolio-featured-image-wrap">
			<?php
			if ( ! empty( $dinofolio_data['featured_image_id'] ) ) {
				echo wp_kses_post(
					wp_get_attachment_image(
						(int) $dinofolio_data['featured_image_id'],
						! empty( $dinofolio_data['featured_image_size'] ) ? $dinofolio_data['featured_image_size'] : 'large',
						false,
						array(
							'class' => 'dinofolio-featured-image',
							'alt'   => esc_attr( $dinofolio_data['post_title'] ),
							'sizes' => '(max-width: 768px) 100vw, 1200px',
						)
					)
				);
			} else {
				?>
				<img class="dinofolio-featured-image" src="<?php echo esc_url( $dinofolio_data['featured_image_url'] ); ?>" alt="<?php echo esc_attr( $dinofolio_data['post_title'] ); ?>">
				<?php
			}
			?>
		</div>
	<?php endif; ?>

	<section class="dinofolio-project-details">
		<h2 class="dinofolio-section-heading"><?php esc_html_e( 'Project Details', 'dinofolio' ); ?></h2>
		<div class="dinofolio-project-details-card">
			<div class="dinofolio-single-meta-grid">
				<?php if ( 'off' !== $dinofolio_data['date_display'] ) : ?>
					<div class="dinofolio-meta-item">
						<span class="dinofolio-meta-label"><?php echo esc_html( $dinofolio_data['date_label'] ? $dinofolio_data['date_label'] : __( 'Date', 'dinofolio' ) ); ?>:</span>
						<span class="dinofolio-meta-value"><?php echo esc_html( $dinofolio_data['date_value'] ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $dinofolio_data['attributes'] ) && is_array( $dinofolio_data['attributes'] ) ) : ?>
					<?php foreach ( $dinofolio_data['attributes'] as $dinofolio_attribute ) : ?>
						<div class="dinofolio-meta-item">
							<?php if ( ! empty( $dinofolio_attribute['label'] ) ) : ?>
								<span class="dinofolio-meta-label"><?php echo esc_html( $dinofolio_attribute['label'] ); ?>:</span>
							<?php endif; ?>
							<?php if ( ! empty( $dinofolio_attribute['value'] ) ) : ?>
								<span class="dinofolio-meta-value"><?php echo esc_html( $dinofolio_attribute['value'] ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ( ! empty( $dinofolio_data['external_url'] ) ) : ?>
					<div class="dinofolio-meta-item dinofolio-meta-item--action">
						<a class="dinofolio-meta-button button wp-element-button wp-block-button__link" href="<?php echo esc_url( $dinofolio_data['external_url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $dinofolio_data['button_label'] ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php
	/**
	 * Fires after single portfolio project details.
	 *
	 * @param array $dinofolio_data Template data.
	 */
	do_action( 'dinofolio_single_meta_after_details', $dinofolio_data );
	?>

	<?php if ( ! empty( $dinofolio_data['related_projects'] ) && is_array( $dinofolio_data['related_projects'] ) ) : ?>
		<div
			class="dinofolio-related-projects is-<?php echo esc_attr( $dinofolio_related_style ); ?> dinofolio-related-columns-<?php echo esc_attr( $dinofolio_related_columns ); ?>"
			style="--dinofolio-related-columns: <?php echo esc_attr( $dinofolio_related_columns ); ?>;"
		>
			<?php if ( ! empty( $dinofolio_data['related_projects_title'] ) ) : ?>
				<h2 class="dinofolio-section-heading dinofolio-related-projects-title"><?php echo esc_html( $dinofolio_data['related_projects_title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( $dinofolio_is_carousel ) : ?>
				<div class="dinofolio-related-carousel-shell">
					<div class="dinofolio-related-carousel" data-dinofolio-carousel data-columns="<?php echo esc_attr( $dinofolio_related_columns ); ?>">
						<button type="button" class="dinofolio-carousel-nav dinofolio-carousel-prev" aria-label="<?php esc_attr_e( 'Previous projects', 'dinofolio' ); ?>">
							<svg class="dinofolio-carousel-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
								<path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</button>
						<div class="dinofolio-related-carousel-viewport">
							<ul class="dinofolio-related-list dinofolio-related-carousel-track">
								<?php foreach ( $dinofolio_data['related_projects'] as $dinofolio_related_item ) : ?>
									<?php require DINOFOLIO_PATH . 'templates/parts/related-project-card.php'; ?>
								<?php endforeach; ?>
							</ul>
						</div>
						<button type="button" class="dinofolio-carousel-nav dinofolio-carousel-next" aria-label="<?php esc_attr_e( 'Next projects', 'dinofolio' ); ?>">
							<svg class="dinofolio-carousel-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
								<path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</button>
					</div>
				</div>
			<?php else : ?>
				<ul class="dinofolio-related-list dinofolio-related-grid">
					<?php foreach ( $dinofolio_data['related_projects'] as $dinofolio_related_item ) : ?>
						<?php require DINOFOLIO_PATH . 'templates/parts/related-project-card.php'; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
