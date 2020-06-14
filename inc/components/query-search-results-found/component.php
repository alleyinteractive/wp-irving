<?php
/**
 * Search results found.
 *
 * Display meta information about the search results.
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

			$wp_query = $component->get_config( 'wp_query' );

			return $component->set_config(
				'content',
				sprintf(
					// translators: %1$s - Number of search results, %2$s - search term.
					__( '%1$s results found for "%2$s"', 'wp-irving' ),
					number_format( $wp_query->found_posts ),
					$wp_query->get( 's' )
				)
			);
		},
	]
);
