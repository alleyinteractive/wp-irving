<?php
/**
 * Example content.
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
		'callback' => function ( array $component ): array {
			$post_id = $component['data_provider']['postId'] ?? get_the_ID();

			$component['name'] = 'html';
			$component['config']['content'] = get_the_author_meta( 'user_nicename', get_post( $post_id )->post_author );

			return $component;
		},
	]
);
