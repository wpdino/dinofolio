<?php
/**
 * Public extension API for DinoFolio Lite / Pro.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

/**
 * Whether the DinoFolio Pro plugin is active.
 *
 * Pro should hook: add_filter( 'dinofolio_is_pro', '__return_true' );
 *
 * @return bool
 */
function dinofolio_is_pro() {
	return (bool) apply_filters( 'dinofolio_is_pro', false );
}

/**
 * Whether a Pro feature is available.
 *
 * Pro can map feature slugs to availability, e.g. `load_more_in_view`, `overlay_styles`.
 *
 * @param string $feature Feature slug.
 * @param array  $context Optional context (component, attributes, etc.).
 * @return bool
 */
function dinofolio_has_feature( $feature, $context = array() ) {
	$feature = sanitize_key( (string) $feature );
	$context = is_array( $context ) ? $context : array();

	return (bool) apply_filters( 'dinofolio_has_feature', false, $feature, $context );
}

/**
 * Fire a namespaced DinoFolio action.
 *
 * @param string $hook  Hook suffix without prefix.
 * @param mixed  ...$args Arguments.
 * @return void
 */
function dinofolio_do_action( $hook, ...$args ) {
	do_action( 'dinofolio_' . $hook, ...$args );
}

/**
 * Apply a namespaced DinoFolio filter.
 *
 * @param string $hook  Hook suffix without prefix.
 * @param mixed  $value Value to filter.
 * @param mixed  ...$args Extra arguments.
 * @return mixed
 */
function dinofolio_apply_filters( $hook, $value, ...$args ) {
	return apply_filters( 'dinofolio_' . $hook, $value, ...$args );
}
