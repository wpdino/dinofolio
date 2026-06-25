<?php
/**
 * Related project card partial.
 *
 * @package DinoFolio
 * @var array $dinofolio_related_item Related project data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $dinofolio_related_item ) || ! is_array( $dinofolio_related_item ) ) {
	return;
}

?>
<li class="dinofolio-related-card">
	<a class="dinofolio-related-link" href="<?php echo esc_url( $dinofolio_related_item['url'] ); ?>">
		<?php if ( ! empty( $dinofolio_related_item['thumbnail_id'] ) ) : ?>
			<span class="dinofolio-related-thumb-wrap">
				<?php
				echo wp_kses_post(
					wp_get_attachment_image(
						(int) $dinofolio_related_item['thumbnail_id'],
						'dinofolio-related-card',
						false,
						array(
							'class' => 'dinofolio-related-thumb',
							'alt'   => ! empty( $dinofolio_related_item['title'] ) ? $dinofolio_related_item['title'] : '',
							'sizes' => '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 400px',
						)
					)
				);
				?>
			</span>
		<?php elseif ( ! empty( $dinofolio_related_item['thumbnail_url'] ) ) : ?>
			<span class="dinofolio-related-thumb-wrap">
				<img class="dinofolio-related-thumb" src="<?php echo esc_url( $dinofolio_related_item['thumbnail_url'] ); ?>" alt="<?php echo esc_attr( $dinofolio_related_item['title'] ); ?>">
			</span>
		<?php endif; ?>
		<span class="dinofolio-related-title"><?php echo esc_html( $dinofolio_related_item['title'] ); ?></span>
	</a>
</li>
