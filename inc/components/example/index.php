<?php
/**
 * Example content.
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
		/**
		 * Callback on the component which executes right before output.
		 */
		'callback' => function ( array $component ): array {
			return $component;
		},
	]
);
