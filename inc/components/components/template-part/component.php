<?php
/**
 * Template part loader.
 *
 * Load a template part.
 *
 * Irving implementation of get_template_part().
 *
 * @see https://developer.wordpress.org/reference/functions/get_template_part/
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Irving\Templates;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback'   => function( array $config ): array {
			if ( ! empty( $config['name'] ) ) {
				$config['templates'][] = $config['slug'] . '-' . $config['name'];
			}

			// Fallback to just the slug.
			$config['templates'][] = $config['slug'];

			return $config;
		},
		'children_callback' => function( array $children, array $config ): array {
			// Loop through templates until something is hydrated successfully.
			foreach ( $config['templates'] as $template_part_slug ) {
				$children = Templates\hydrate_template_parts(
					[
						'name' => 'template-parts/' . $template_part_slug,
					]
				);

				// Hydrated successfully.
				if ( ! empty( $children ) ) {
					break;
				}
			}

			return $children;
		},
	]
);
