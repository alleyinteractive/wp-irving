<?php
/**
 * Post excerpt.
 *
 * Get the post excerpt.
 *
 * @todo Support for modifying the excerpt length easily. Perhaps options for
 *       character count, sentence count, etc.
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
			$post_id = $config['post_id'] ?: 0;

			if ( ! $post_id ) {
				return $config;
			}

			return array_merge(
				$config,
				[
					 // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
					'content' => apply_filters( 'the_excerpt', get_the_excerpt( $post_id ) ),
					'html'    => true,
				]
			);
		},
	]
);
