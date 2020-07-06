<?php
/**
 * Post featured media.
 *
 * Display the post featured media.
 *
 * @todo Hook up some UI on the backend to implement the different featured
 * media options; ex. featured image, custom image, no image, or video.
 * @todo Consider creating a React component for this functionality.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

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

			// Get the post ID from a context provider.
			$post_id = $component->get_config( 'post_id' );

			// Featured image.
			$component->append_child(
				[
					'name'   => 'irving/post-featured-image',
					'config' => $component->get_config(),
				]
			);

			return $component;
		},
	]
);
