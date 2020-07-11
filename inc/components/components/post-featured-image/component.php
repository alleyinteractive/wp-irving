<?php
/**
 * Post featured image.
 *
 * Get the post featured image.
 *
 * @todo Add filters to make customizing this easier.
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
		'config_callback' => function( array $config ): array {
			// phpcs:ignore WordPress.PHP.DisallowShortTernary
			$post_id = $config['post_id'] ?: 0;

			if ( ! $post_id ) {
				return $config;
			}

			$thumbnail_id = get_post_thumbnail_id( $post_id );

			return array_merge(
				$config,
				[
					'alt'     => get_image_alt( $thumbnail_id ),
					'caption' => wp_get_attachment_caption( $thumbnail_id ),
					'credit'  => get_post_meta( $thumbnail_id, 'credit', true ),
					'src'     => get_the_post_thumbnail_url( $post_id ),
				]
			);
		},
	]
);
