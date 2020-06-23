<?php
/**
 * Post featured media.
 *
 * Display the post featured media.
 *
 * @todo Update to remove all material UI.
 * @todo Consider creating a React component for this functionality.
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

			$component->append_child(
				[
					'name' => 'irving/post-featured-image',
					'config' => [
						'aspect_ratio' => $component->get_config( 'aspect_ratio' ),
					],
				]
			);

			return $component;
		},
	]
);
