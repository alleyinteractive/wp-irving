<?php
/**
 * Archive title.
 *
 * Get the archive title.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function( array $config ): array {
			$config['content'] = get_the_archive_title();

			return $config;
		},
	]
);
