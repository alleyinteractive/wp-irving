<?php
/**
 * Site bloginfo.
 *
 * Easily call bloginfo().
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
			$config['content'] = get_bloginfo( $config['show'] );

			return $config;
		},
	]
);
