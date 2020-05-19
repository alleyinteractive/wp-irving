<?php
/**
 * Site name.
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
		'callback' => function(): string {
			return get_bloginfo( 'name' );
		},
	]
);
