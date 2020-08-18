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
		'config_callback' => function ( array $config ): array {

			// Get the WP_Query object from a context provider.
			$wp_query = $config['wp_query'];

			// Convert to integer and set to page 1 if needed.
			$current_page = absint( $wp_query->get( 'paged' ) );
			$total_pages  = absint( $wp_query->max_num_pages );

			if ( 0 === $current_page ) {
				$current_page = 1;
			}

			return array_merge(
				$config,
				[
					'base_url'     => $config['base_url'] ?? get_base_url( $wp_query ),
					'current_page' => $current_page,
					'total_pages'  => $total_pages,
				]
			);
		},
	]
);

/**
 * Get the base URL based on the query.
 *
 * @param \WP_Query $wp_query The WP Query object.
 * @return string
 */
function get_base_url( $wp_query ) {
	// Default to '/' for the base URL.
	$base_url = '/';

	if ( $wp_query->is_archive() ) {
		$term_id = $wp_query->get_queried_object_id();
		$url     = get_term_link( $term_id );
		return wp_parse_url( $url, PHP_URL_PATH );
	}

	return $base_url;
}
