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

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'children_callback' => function( array $children, array $config ): array {
			// This wraps the irving/post-featured-image component.
			return [
				new Component( 'irving/post-featured-image' ),
			];
		},
	]
);
