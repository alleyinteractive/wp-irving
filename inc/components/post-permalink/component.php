<?php
/**
 * Post permalink.
 *
 * Get the post permalink.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Irving\Component;

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
\WP_Irving\get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function( Component $component ): Component {

			// Get the post ID from a context provider.
			$post_id = $component->get_config( 'post_id' );

			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				return $component;
			}

			return $component->set_config( 'href', get_permalink( $post_id ) );
		},
	]
);
