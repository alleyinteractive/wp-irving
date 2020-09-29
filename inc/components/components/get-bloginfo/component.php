<?php
/**
 * Provider for get_bloginfo().
 *
 * Gets a specific value about the current site and provides it as a context.
 *
 * @package WP_Irving
 *
 * @see https://developer.wordpress.org/reference/functions/get_bloginfo/
 */

namespace WP_Irving\Components;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function ( array $config ): array {
			$config['value'] = get_bloginfo( $config['key'] );
			return $config;
		},
	]
);
