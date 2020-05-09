<?php
/**
 * Iterate the global WP_Query object so the next post is available as the
 * global Post object.
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
		/**
		 * Callback on the component which executes right before output.
		 */
		'callback' => function ( array $component ): array {
			the_post();
			$component['name'] = '';
			return $component;
		},
	]
);
