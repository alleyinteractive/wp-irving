<?php
/**
 * Social sharing.
 *
 * Displays a list of platforms to share content on.
 *
 * @package Irving_Components
 */

namespace WP_Irving\Components;

use WP_Post;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function( array $config ): array {

			// Ensure we have a valid post ID.
			$post = get_post( $config['post_id'] );

			// Bail early if not a post.
			if ( ! $post instanceof WP_Post ) {
				return $config;
			}

			return array_merge(
				$config,
				[
					'description' => apply_filters( 'the_excerpt', get_the_excerpt( $post ) ), // phpcs:ignore
					'image_url'   => get_the_post_thumbnail_url( $post, 'full' ),
					'title'       => get_the_title( $post ),
					'url'         => get_the_permalink( $post ),
				]
			);
		},
	]
);
