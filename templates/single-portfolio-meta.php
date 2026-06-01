<?php
/**
 * Single portfolio meta template.
 *
 * Override in theme: dinofolio/single-portfolio-meta.php
 *
 * @package DinoFolio
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $data ) || ! is_array( $data ) ) {
	return;
}

$related_style   = ! empty( $data['related_projects_style'] ) ? $data['related_projects_style'] : 'grid';
$is_carousel     = ( 'carousel' === $related_style );
$related_columns = isset( $data['related_projects_count'] ) ? max( 2, min( 5, (int) $data['related_projects_count'] ) ) : 3;
$related_partial = trailingslashit( DINOFOLIO_PATH ) . 'templates/parts/related-project-card.php';

?>
<div class="dinofolio-single-meta">
	<?php if ( ! empty( $data['featured_image_url'] ) ) : ?>
		<div class="dinofolio-featured-image-wrap">
			<?php
			if ( ! empty( $data['featured_image_id'] ) ) {
				echo wp_get_attachment_image(
					(int) $data['featured_image_id'],
					! empty( $data['featured_image_size'] ) ? $data['featured_image_size'] : 'large',
					false,
					array(
						'class' => 'dinofolio-featured-image',
						'alt'   => esc_attr( $data['post_title'] ),
						'sizes' => '(max-width: 768px) 100vw, 1200px',
					)
				);
			} else {
				?>
				<img class="dinofolio-featured-image" src="<?php echo esc_url( $data['featured_image_url'] ); ?>" alt="<?php echo esc_attr( $data['post_title'] ); ?>">
				<?php
			}
			?>
		</div>
	<?php endif; ?>

	<section class="dinofolio-project-details">
		<h2 class="dinofolio-section-heading"><?php esc_html_e( 'Project Details', 'dinofolio' ); ?></h2>
		<div class="dinofolio-project-details-card">
			<div class="dinofolio-single-meta-grid">
				<?php if ( 'off' !== $data['date_display'] ) : ?>
					<div class="dinofolio-meta-item">
						<span class="dinofolio-meta-label"><?php echo esc_html( $data['date_label'] ? $data['date_label'] : __( 'Date', 'dinofolio' ) ); ?>:</span>
						<span class="dinofolio-meta-value"><?php echo esc_html( $data['date_value'] ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $data['attributes'] ) && is_array( $data['attributes'] ) ) : ?>
					<?php foreach ( $data['attributes'] as $attribute ) : ?>
						<div class="dinofolio-meta-item">
							<?php if ( ! empty( $attribute['label'] ) ) : ?>
								<span class="dinofolio-meta-label"><?php echo esc_html( $attribute['label'] ); ?>:</span>
							<?php endif; ?>
							<?php if ( ! empty( $attribute['value'] ) ) : ?>
								<span class="dinofolio-meta-value"><?php echo esc_html( $attribute['value'] ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ( ! empty( $data['external_url'] ) ) : ?>
					<div class="dinofolio-meta-item dinofolio-meta-item--action">
						<a class="dinofolio-meta-button button wp-element-button wp-block-button__link" href="<?php echo esc_url( $data['external_url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $data['button_label'] ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ( ! empty( $data['related_projects'] ) && is_array( $data['related_projects'] ) ) : ?>
		<div
			class="dinofolio-related-projects is-<?php echo esc_attr( $related_style ); ?> dinofolio-related-columns-<?php echo esc_attr( $related_columns ); ?>"
			style="--dinofolio-related-columns: <?php echo esc_attr( $related_columns ); ?>;"
		>
			<?php if ( ! empty( $data['related_projects_title'] ) ) : ?>
				<h2 class="dinofolio-section-heading dinofolio-related-projects-title"><?php echo esc_html( $data['related_projects_title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( $is_carousel ) : ?>
				<div class="dinofolio-related-carousel-shell">
					<div class="dinofolio-related-carousel" data-dinofolio-carousel data-columns="<?php echo esc_attr( $related_columns ); ?>">
						<button type="button" class="dinofolio-carousel-nav dinofolio-carousel-prev" aria-label="<?php esc_attr_e( 'Previous projects', 'dinofolio' ); ?>">
							<svg class="dinofolio-carousel-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
								<path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</button>
						<div class="dinofolio-related-carousel-viewport">
							<ul class="dinofolio-related-list dinofolio-related-carousel-track">
								<?php foreach ( $data['related_projects'] as $related_item ) : ?>
									<?php
									if ( file_exists( $related_partial ) ) {
										include $related_partial;
									}
									?>
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
					<?php foreach ( $data['related_projects'] as $related_item ) : ?>
						<?php
						if ( file_exists( $related_partial ) ) {
							include $related_partial;
						}
						?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
