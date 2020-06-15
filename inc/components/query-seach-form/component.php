<?php
/**
 * Search form.
 *
 * Input field for the search query.
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
\WP_Irving\get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function( Component $component ): Component {
			$search_term = $component->get_config( 'wp_query' )->get( 's' ) ?? '';
			return $component ->set_config( 'search_term', $search_term );
		},
	]
);
