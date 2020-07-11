<?php
/**
 * Query pagination.
 *
 * Pagination UI for a query.
 *
 * @todo Better automate the `base_url` and `pagination_format` config values
 *       to work automatically.
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
