<?php
/**
 * Post title.
 *
 * Get the post title.
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
		'callback' => function( Component $component ): Component {

			// Get the post ID from a context provider.
			$post_id = $component->get_config( 'post_id' );

			return $component->set_config( 'content', html_entity_decode( get_the_title( $post_id ) ) );
		},
	]
);
