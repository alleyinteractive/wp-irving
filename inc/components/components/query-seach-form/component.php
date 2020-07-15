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
		'config_callback' => function ( array $config ): array {

			// Get the WP_Query object is always set via context.
			$wp_query = $config['wp_query'];

			$config['search_term'] = $wp_query->get( 's' ) ?? '';

			return $config;
		},
	]
);
