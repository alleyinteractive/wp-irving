<?php
/**
 * Search results found.
 *
 * Display meta information about the search results.
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

			// Get the WP_Query object is always set via context.
			$wp_query = $config['wp_query'];

			// Convert to integer and set to page 1 if needed.
			$current_page = absint( $wp_query->get( 'paged' ) );
			if ( 0 === $current_page ) {
				$current_page = 1;
			}

			$component['content'] = sprintf(
				/**
				 * Translators:
				 * %1$s - Number of results
				 * %2$s - Search string.
				 * %3$s - Current page.
				 * %4$s - Number of pages found.
				 */
				$config['content_format'],
				number_format( absint( $wp_query->found_posts ) ),
				esc_html( $wp_query->get( 's' ) ),
				number_format( absint( $current_page ) ),
				number_format( absint( $wp_query->max_num_pages ) )
			);

			return $component;
		},
	]
);
