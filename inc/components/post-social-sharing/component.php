<?php
/**
 * Social Sharing Component.
 *
 * Displays list of social sharing icons.
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
			if ( 0 === $post_id ) {
				$post_id = get_the_ID();
			}

			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				return $component;
			}

			$component->merge_config(
				[
					'post_excerpt'   => get_the_excerpt(),
					'post_permalink' => get_the_permalink(),
					'post_thumbnail' => get_the_post_thumbnail_url( get_the_ID(), 'full' ),
					'post_title'     => get_the_title(),
				]
			);

			return $component;
		},
	]
);
