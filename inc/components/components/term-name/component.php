<?php
/**
 * Term name.
 *
 * Get the term name.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Term;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function ( array $config ): array {

			// Term ID is set via context and should always have a value.
			$term = get_term( $config['term_id'] ?? 0 );

			// Bail if the term is invalid.
			if ( is_wp_error( $term ) || ! $term instanceof WP_Term ) {
				return $config;
			}

			$config['content'] = html_entity_decode( $term->name, ENT_QUOTES, get_bloginfo( 'charset' ) );

			return $config;
		},
	]
);
