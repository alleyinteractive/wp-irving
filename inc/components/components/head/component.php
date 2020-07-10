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
get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'children_callback' => function( $children, $config ): array {

			// Get the context of the head.
			$context = $config['context'] ?? '';

			// Inject a default title tag.
			$children[] = new Component(
				'title',
				[
					'children' => [
						( 'defaults' === $context ) ?
							get_bloginfo( 'name' ) :
							wp_title( '&raquo;', false ),
					],
				]
			);

			/**
			 * Filter the children values for all instances of this component.
			 *
			 * @param array $children Config array for this component instance.
			 */
			return apply_filters( "wp_irving_{$context}_head_component_children", $children );
		},
	]
);
