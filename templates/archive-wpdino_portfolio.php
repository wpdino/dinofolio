<?php
/**
 * Portfolio post type archive template.
 *
 * Override in theme: archive-wpdino_portfolio.php
 *
 * @package DinoFolio
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

$post_type_object      = get_post_type_object( 'wpdino_portfolio' );
$dinofolio_description = get_the_archive_description();
$dinofolio_display     = class_exists( 'WPDINO_Portfolio_Display' ) ? \WPDINO_Portfolio_Display::get_instance() : null;
$dinofolio_listing     = '';

if ( $dinofolio_display ) {
	$dinofolio_listing = $dinofolio_display->render_portfolio_listing(
		$dinofolio_display->get_archive_listing_attributes()
	);
}
?>

<div class="dinofolio-taxonomy-archive dinofolio-portfolio-archive">
	<header class="dinofolio-taxonomy-header page-header alignwide">
		<?php
		if ( $post_type_object && ! empty( $post_type_object->labels->name ) ) {
			echo '<h1 class="page-title dinofolio-taxonomy-title">' . esc_html( $post_type_object->labels->name ) . '</h1>';
		} else {
			the_archive_title( '<h1 class="page-title dinofolio-taxonomy-title">', '</h1>' );
		}
		?>

		<?php if ( $dinofolio_description ) : ?>
			<div class="archive-description dinofolio-taxonomy-description">
				<?php echo wp_kses_post( wpautop( $dinofolio_description ) ); ?>
			</div>
		<?php endif; ?>
	</header>

	<?php if ( $dinofolio_listing ) : ?>
		<div class="dinofolio-taxonomy-listing">
			<?php echo $dinofolio_listing; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php else : ?>
		<div class="dinofolio-no-posts">
			<?php esc_html_e( 'Portfolio display module is not available.', 'dinofolio' ); ?>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
