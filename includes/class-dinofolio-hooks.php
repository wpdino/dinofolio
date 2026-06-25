<?php
/**
 * Extension hooks for DinoFolio integrations.
 *
 * @package DinoFolio
 */

namespace DinoFolio;

defined( 'ABSPATH' ) || exit;

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
