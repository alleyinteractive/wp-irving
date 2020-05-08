<?php
/**
 * Post excerpt.
 *
 * @package Irving_Components
 */

if ( ! function_exists( '\WP_Irving\Components\register_component_from_config' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
\WP_Irving\Components\register_component_from_config(
	__DIR__ . '/component',
	[
		/**
		 * Callback on the component which executes right before output.
		 */
		'callback' => function ( array $component ): string {
			$post_id = $component['data_provider']['postId'] ?? get_the_ID();

			return esc_html( get_the_excerpt( $post_id ) );
		},
	]
);
