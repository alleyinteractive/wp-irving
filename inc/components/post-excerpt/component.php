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

			$post_excerpt = apply_filters( 'the_excerpt', get_the_excerpt( $post_id ) ); // phpcs:ignore

			return $component
				->set_config( 'content', $post_excerpt )
				->set_config( 'html', true );
		},
	]
);
