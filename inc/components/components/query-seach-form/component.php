<?php
/**
 * Search form.
 *
 * Form with an input field for search.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function( Component $component ): Component {

			// Get the WP_Query object from a context provider.
			$wp_query = $component->get_config( 'wp_query' );

			return $component->set_config( 'search_term', $wp_query->get( 's' ) ?? '' );
		},
	]
);
