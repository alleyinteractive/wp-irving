<?php
/**
 * Site bloginfo.
 *
 * @package Irving_Components
 *
 * @see https://developer.wordpress.org/reference/functions/get_bloginfo/
 */

use WP_Irving\Component;

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
\WP_Irving\get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function( Component $component ): Component {

			// get_bloginfo _should_ return a string no matter what, but let's not take chances.
			$content = (string) get_bloginfo( $component->get_config( 'show' ) ?? 'name' );

			// Set `content` and rename to irving/text.
			return $component
				->set_config( 'content', $content )
				->set_name( 'irving/text' );
		},
	]
);
