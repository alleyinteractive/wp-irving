<?php
/**
 * Head.
 *
 * Manage the <head>.
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
		'children_callback' => function( array $children, array $config ): array {

			// Get the context of the head.
			$context = $config['context'] ?? '';

			// Inject a default title tag.
			$children[] = new Component(
				'title',
				[
					'children' => [
						( 'defaults' === $context ) ?
							get_bloginfo( 'name' ) :
							html_entity_decode( wp_get_document_title() ),
					],
				]
			);

			return $children;
		},
	]
);
