<?php
/**
 * Social links.
 *
 * Display a series of social media icons with links
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Register the component.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function ( array $config ): array {
			$config['platforms'] = $config['platforms'] ?? [];
			$platforms = apply_filters( 'wp_irving_social_links_platforms', $config['platforms'] );

			$config['platforms'] = is_array( $platforms ) ? $platforms : [];

			return $config;
		}
	]
);
