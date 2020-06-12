<?php
/**
 * Text.
 *
 * Concatenate and render a string.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Irving\Component;

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
\WP_Irving\get_registry()->register_component_from_config( __DIR__ . '/component' );

/**
 * Output the content config value as a text node instead of a component.
 *
 * @todo Deterine how text and strings should actually work, and target
 * aliases better.
 *
 * @param array $component Component as an array.
 * @return array|string
 */
function serialize_text_component( array $component ) {

	if (
		! empty( $component['config']->content ?? '' )
		&& (
			'irving/text' === ( $component['name'] ?? '' )
			|| 'irving/post-title' === ( $component['name'] ?? '' )
		)
	) {
		$component['children'][] = $component['config']->content ?? '';
	}

	return $component;
}
add_filter( 'wp_irving_serialize_component_array', __NAMESPACE__ . '\serialize_text_component' );
