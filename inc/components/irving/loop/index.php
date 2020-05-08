<?php
/**
 * The Loop utility.
 *
 * @package Irving_Components
 */

if ( ! function_exists( '\WP_Irving\Components\register_component_from_config' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
\WP_Irving\Components\register_component_from_config(
	__DIR__ . '/component',
	[
		/**
		 * Callback on the component which executes right before output.
		 */
		'callback' => function ( array $component ): array {
			$new_components     = [];
			$component_template = $component['children'];
			$loop_count         = absint( $component['config']['loop'] ?? 0 );

			$component['children'] = [];

			if ( 0 === $loop_count ) {
				while ( have_posts() ) {
					the_post();

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
			}

			$component['name'] = '';
			return $component;
		},
	]
);
