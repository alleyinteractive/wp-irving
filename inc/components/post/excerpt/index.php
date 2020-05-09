<?php
/**
 * Post excerpt.
 *
 * @package Irving_Components
 */

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
\WP_Irving\get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function ( array $component ): string {
			$post_id = $component['data_provider']['postId'] ?? get_the_ID();
			return esc_html( get_the_excerpt( $post_id ) );
		},
	]
);
