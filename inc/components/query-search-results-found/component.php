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

			// Get the WP_Query object from a context provider.
			$wp_query = $component->get_config( 'wp_query' );

			// Convert to integer and set to page 1 if needed.
			$current_page = absint( $wp_query->get( 'paged' ) );
			if ( 0 === $current_page ) {
				$current_page = 1;
			}

			return $component->set_config(
				'content',
				sprintf(
					/**
					 * Translators:
					 * %1$s - Search string.
					 * %2$s - Number of results
					 * %3$s - Current page.
					 * %4$s - Number of pages found.
					 */
					$component->get_config( 'content_format' ),
					esc_html( $wp_query->get( 's' ) ),
					number_format( absint( $wp_query->found_posts ) ),
					number_format( absint( $current_page ) ),
					number_format( absint( $wp_query->max_num_pages ) )
				)
			);
		},
	]
);
