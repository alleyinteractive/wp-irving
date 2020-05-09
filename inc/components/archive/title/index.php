<?php
/**
 * Archive title.
 *
 * Replaces the component with a string result of `get_the_archive_title()`.
 *
 * @package Irving_Components
 *
 * @see https://developer.wordpress.org/reference/functions/get_the_archive_title/
 */

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
\WP_Irving\get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => 'get_the_archive_title',
	]
);
