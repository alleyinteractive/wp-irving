<?php
/**
 * Post content.
 *
 * @package Irving_Components
 *
 * @todo Reference the Gutenberg Content component in wp-components to
 *       determine some better logic around this process.
 *
 * @param array $component['data_provider']['postId'] Post ID, defaults to global.
 * @return array
 */

use function WP_Irving\Components\register_component_from_config;

register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function ( $component ) {

			// Use the data provider, or fallback to global.
			$post_id               = $component['data_provider']['postId'] ?? get_the_ID();
			$post                  = get_post( $post_id );
			$component['children'] = \WP_Irving\Templates\convert_blocks_to_components( parse_blocks( $post->post_content ) );

			// Ensure the data provider executes on these new children.
			$component = \WP_Irving\Templates\handle_data_provider( $component );

			// Placeholders. Replace with a better FE component.
			$component['name']                = 'irving/container';
			$component['config']['themeName'] = 'fullBleed';

			return $component;
		},
	]
);
