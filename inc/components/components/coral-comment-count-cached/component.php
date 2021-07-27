<?php
/**
 * Coral Comment Count Cached.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Register the component.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function ( array $config ): array {
			$config['count'] = (int) get_post_meta( $config['post_id'], 'coral_comment_count', true );
			return $config;
		},
	]
);
