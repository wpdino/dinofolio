<?php
/**
 * Related project card partial.
 *
 * @package DinoFolio
 * @var array $related_item Related project data.
 */

if ( ! defined( 'ABSPATH' ) || empty( $related_item ) || ! is_array( $related_item ) ) {
	return;
}

?>
<li class="dinofolio-related-card">
	<a class="dinofolio-related-link" href="<?php echo esc_url( $related_item['url'] ); ?>">
		<?php if ( ! empty( $related_item['thumbnail_url'] ) ) : ?>
			<img class="dinofolio-related-thumb" src="<?php echo esc_url( $related_item['thumbnail_url'] ); ?>" alt="<?php echo esc_attr( $related_item['title'] ); ?>">
		<?php endif; ?>
		<span class="dinofolio-related-title"><?php echo esc_html( $related_item['title'] ); ?></span>
	</a>
</li>
