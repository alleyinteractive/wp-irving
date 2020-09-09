<?php
/**
 * Post permalink.
 *
 * Get the post permalink.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Post;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function ( array $config ): array {
			$post_id = $config['post_id'];

			// Bail early if we have no post ID.
			if ( ! $post_id ) {
				return $config;
			}

			$permalink = get_the_permalink( $post_id );

			// Bail if we have no permalink.
			if ( is_wp_error( $permalink ) ) {
				return $config;
			}

			if ( ! empty( $config['fragment'] ) ) {
				$permalink .= '#' . $config['fragment'];
			}

			return array_merge(
				$config,
				[
					'href' => $permalink,
				]
			);
		},
	]
);
