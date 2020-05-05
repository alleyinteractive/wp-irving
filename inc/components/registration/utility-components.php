<?php
/**
 * Registration for utility commponents.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Utility component that executes a new WP_Query, and uses
 * $component['children'] as a temmplate to loop through. Uses a `postId` data
 * provider to pass along the context to children components.
 *
 * @todo Decide how we can do this better.
 *
 * @return array
 */
register_component(
	'irving/wp_query',
	[
		'callback' => function( $component ) {

			// Execute a query. Probably allow-list some keys here.
			$query = new \WP_Query( $component['config'] );

			// Use the array of children components as a template.
			$component_template = $component['children'];

			$new_components = [];

			while ( $query->have_posts() ) {
				$query->the_post();

				$loop_instance = $component_template;

				foreach ( $loop_instance as &$new_component ) {
					$new_component['data_provider']['postId'] = get_the_ID();
				}

				$new_components = array_merge( $new_components, $loop_instance );
			}

			wp_reset_query();

			$component['children'] = $new_components;

			$component['name'] = '';

			return $component;
		},
	]
);

/**
 * The children of this component will render when the meta conditional is true.
 *
 * @param string $key   Meta key.
 * @param mixed  $value Value to match against.
 * @return array
 */
register_component(
	'irving/meta_conditional',
	[
		'callback' => function( $component ) {
			return $component;
		},
	]
);
