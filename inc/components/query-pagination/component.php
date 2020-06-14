<?php
/**
 * Query pagination.
 *
 * Pagination UI for a query.
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

			// Convert to integer and set to page 1 if needed.
			$current_page = absint( $wp_query->get( 'paged' ) );
			if ( 0 === $current_page ) {
				$current_page = 1;
			}

			$component->set_config( 'current_page', $current_page );
			$component->set_config( 'total_pages', $wp_query->max_num_pages );

			return $component;
		},
	]
);
