<?php
/**
 * Site name.
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
			return get_bloginfo( 'name' );
		},
	]
);
