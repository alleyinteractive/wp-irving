<?php
/**
 * Social sharing.
 *
 * Displays a list of platforms to share content on.
 *
 * @package Irving_Components
 */

namespace WP_Irving;

use WP_Irving\Component;

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function( Component $component ): Component {

			// Get the post ID from a context provider, or fallback to the global.
			$post_id = $component->get_config( 'post_id' );

			// Validate the post.
			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				return $component;
			}

			$component->merge_config(
				[
					'description' => apply_filters( 'the_excerpt', get_the_excerpt( $post_id ) ), // phpcs:ignore
					'image_url'   => get_the_post_thumbnail_url( $post_id, 'full' ),
					'title'       => get_the_title( $post_id ),
					'url'         => get_the_permalink( $post_id ),
				]
			);

			return $component;
		},
	]
);
