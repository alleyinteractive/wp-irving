<?php
/**
 * Functions.
 *
 * @package WP_Irving
 */

namespace WP_Irving\INC;

/**
 * WP Irving REST API namespace.
 *
 * @return string
 */
function wp_irving_rest_namespace() {

	/**
	 * Filter API namespace.
	 *
	 * @param string $namespace Irving namespace.
	 */
	return apply_filters( 'wp_irving_rest_namespace', 'irving' );
}

/**
 * WP Irving REST API version.
 *
 * @return string
 */
function wp_irving_rest_version() {

	/**
	 * Filter API version.
	 *
	 * @since 0.1.0
	 *
	 * @param string $version WP Irving version.
	 */
	return apply_filters( 'wp_irving_rest_version', 'v1' );
}
