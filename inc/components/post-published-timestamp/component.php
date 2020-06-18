<?php
/**
 * Post published timestamp.
 *
 * Get the post published timestamp.
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

			return $component->set_config(
				'content',
				sprintf(
					/**
					 * Translators:
					 * %1$s - Published timestamp.
					 * %2$s - Modified timestamp.
					 */
					$component->get_config( 'content_format' ),
					get_the_date( $component->get_config( 'published_date_format' ), $post_id ),
					get_the_modified_date( $component->get_config( 'modified_date_format' ), $post_id )
				)
			);
		},
	]
);
