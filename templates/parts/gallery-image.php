<?php
/**
 * Single portfolio gallery image.
 *
 * @package DinoFolio
 * @since 1.0.0
 *
 * @var array  $dinofolio_gallery_image Gallery image data.
 * @var string $dinofolio_gallery_group Lightbox group id.
 * @var array  $dinofolio_data          Template data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $dinofolio_gallery_image ) || ! is_array( $dinofolio_gallery_image ) ) {
	return;
}

$dinofolio_slide_class = ! empty( $dinofolio_gallery_slide_class ) ? $dinofolio_gallery_slide_class : '';
?>
<figure class="dinofolio-gallery-item<?php echo esc_attr( $dinofolio_slide_class ? ' ' . $dinofolio_slide_class : '' ); ?>">
	<a
		href="<?php echo esc_url( $dinofolio_gallery_image['full_url'] ); ?>"
		class="glightbox dinofolio-lightbox-link"
		data-glightbox="type: image"
		data-gallery="<?php echo esc_attr( $dinofolio_gallery_group ); ?>"
	>
		<?php
		echo wp_kses_post( wp_get_attachment_image(
			(int) $dinofolio_gallery_image['id'],
			! empty( $dinofolio_gallery_image['image_size'] ) ? $dinofolio_gallery_image['image_size'] : 'large',
			false,
			array(
				'class' => 'dinofolio-gallery-image',
				'alt'   => ! empty( $dinofolio_gallery_image['alt'] ) ? $dinofolio_gallery_image['alt'] : esc_attr( $dinofolio_data['post_title'] ),
				'sizes' => isset( $dinofolio_gallery_image_sizes ) ? $dinofolio_gallery_image_sizes : '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw',
			)
		) );
		?>
	</a>
</figure>
