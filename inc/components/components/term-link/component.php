<?php
/**
 * Term link.
 *
 * Get the term link.
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
			$term_id = $config['term_id'] ?? 0;

			// Bail early if we have no term ID.
			if ( ! $term_id ) {
				return $config;
			}
			echo 'yo'; die();

			$link = get_term_link( $term_id );

			// Bail if we have no link.
			if ( is_wp_error( $link ) ) {
				return $config;
			}

			return array_merge(
				$config,
				[
					'href' => $link,
				]
			);
		},
	]
);
