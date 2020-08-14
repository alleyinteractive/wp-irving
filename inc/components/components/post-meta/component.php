<?php
/**
 * Post meta value provider.
 *
 * Gets a specific post meta value and provides it as a context.
 *
 * @todo Deal with non-string meta values.
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
			$post_id  = $config['post_id'] ?: 0;
			$meta_key = $config['meta_key'] ?? null;

			if ( ! $post_id || ! $meta_key ) {
				return $config;
			}

			$config['meta_value'] = get_post_meta( $post_id, $meta_key, true );

			return $config;
		},
	]
);
