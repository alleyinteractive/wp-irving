<?php
/**
 * Post Permalink.
 *
 * @todo Figure out a better way of doing this. Right now we're just mapping to
 *       a material UI link component.
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
			// Use the data provider, or fallback to global.
			$post_id                     = $component['data_provider']['postId'] ?? get_the_ID();
			$component['config']['href'] = get_the_permalink( $post_id );
			$component['name']           = 'material/link';
			return $component;
		},
	]
);
