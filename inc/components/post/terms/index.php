<?php
/**
 * Post Terms.
 *
 * Loop through the tags for a given post and set as child HTML components.
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
		/**
		 * Callback on the component which executes right before output.
		 */
		'callback' => function ( array $component ): array {

			// Use the data provider, or fallback to global.
			$post_id = $component['data_provider']['postId'] ?? get_the_ID();

			$tags = get_the_tags( $post_id );

			if ( ! is_array( $tags ) || empty( $tags ) ) {
				$component['name'] = '';
				return $component;
			}

			$component['children'] = array_map(
				function( $term ) {
					return [
						'name'     => 'irving/html',
						'config' => [
							'content' => sprintf(
								'<a href="%2$s">%1$s</a>',
								$term->name,
								get_term_link( $term )
							),
						],
					];
				},
				$tags
			);

			$component['name'] = '';

			return $component;
		},
	]
);
