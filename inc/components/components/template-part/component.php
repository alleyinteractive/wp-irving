<?php
/**
 * Template part loader.
 *
 * Conditionally render a template part.
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
		'children_callback' => function( array $children, array $config ): array {
			return Templates\hydrate_template_parts(
				[
					'name' => 'template-parts/' . ( $config['slug'] ?? '' ),
				]
			);
		},
	]
);
