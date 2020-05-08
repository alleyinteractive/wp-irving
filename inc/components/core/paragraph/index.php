<?php
/**
 * Convert the core paragraph block to an HTML component.
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
		'callback' => function ( array $component ): array {
			$component['name'] = 'irving/html';
			return $component;
		},
	]
);
