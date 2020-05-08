<?php
/**
 * Post Permalink.
 *
 * @package Irving_Components
 */

if ( ! function_exists( '\WP_Irving\Components\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
\WP_Irving\Components\get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function ( array $component ): string {
			$post_id = $component['data_provider']['postId'] ?? get_the_ID();
			return get_the_permalink( $post_id );
		},
	]
);
