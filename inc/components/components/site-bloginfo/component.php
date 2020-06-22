<?php
/**
 * Site bloginfo.
 *
 * Easily call bloginfo().
 *
 * @package WP_Irving
 *
 * @see https://developer.wordpress.org/reference/functions/get_bloginfo/
 */

namespace WP_Irving\Components;

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function( Component $component ): Component {
			$content = (string) get_bloginfo( $component->get_config( 'show' ) );
			return $component->set_config( 'content', $content );
		},
	]
);
