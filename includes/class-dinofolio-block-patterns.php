<?php
/**
 * Block patterns for DinoFolio.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Registers editor block patterns.
 */
class Block_Patterns {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 25 );
	}

	/**
	 * Register pattern category and patterns.
	 *
	 * @return void
	 */
	public static function register() {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		register_block_pattern_category(
			'dinofolio',
			array(
				'label' => esc_html__( 'DinoFolio', 'dinofolio' ),
			)
		);

		$patterns = array(
			array(
				'title'       => esc_html__( 'Portfolio grid', 'dinofolio' ),
				'description' => esc_html__( 'Three-column portfolio grid with titles, excerpts, and lightbox.', 'dinofolio' ),
				'content'     => '<!-- wp:dinofolio/portfolio {"columns":3,"postsToShow":9,"lightbox":true} /-->',
			),
			array(
				'title'       => esc_html__( 'Portfolio with filters', 'dinofolio' ),
				'description' => esc_html__( 'Grid with category and tag filter bars plus project counts.', 'dinofolio' ),
				'content'     => '<!-- wp:dinofolio/portfolio {"columns":3,"postsToShow":12,"showFilter":true,"showTagFilter":true,"showFilterCount":true,"lightbox":true} /-->',
			),
		);

		foreach ( $patterns as $index => $pattern ) {
			register_block_pattern(
				'dinofolio/' . sanitize_key( 'pattern-' . ( $index + 1 ) ),
				array(
					'title'       => $pattern['title'],
					'description' => $pattern['description'],
					'categories'  => array( 'dinofolio' ),
					'content'     => $pattern['content'],
				)
			);
		}
	}
}