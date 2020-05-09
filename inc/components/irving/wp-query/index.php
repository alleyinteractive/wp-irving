<?php
/**
 * Utility component that executes a new WP_Query, and uses
 * $component['children'] as a temmplate to loop through. Uses a `postId` data
 * provider to pass along the context to children components.
 *
 * @package Irving_Components
 */

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
\WP_Irving\get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function ( array $component ): array {
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
