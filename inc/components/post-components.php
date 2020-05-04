<?php
/**
 * WP helper components.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

register_component(
	'post/title',
	[
		'callback' => function( $component ) {

			// Use the data provider, or fallback to global.
			$post_id = $component['data_provider']['postId'] ?? get_the_ID();

			$title = get_the_title( $post_id );

			if ( ! empty( $title ) ) {
				return html_entity_decode( $title );
			}

			return __( 'Error: no global post context found', 'wp-irving' );
		},
		'data_provider' => [
			'postId' => [
				'type' => 'integer',
			],
		],
	]
);

/**
 * Post content component.
 */
register_component(
	'post/content',
	[
		'callback' => function( $component ) {

			// Use the data provider, or fallback to global.
			$post_id                          = $component['data_provider']['postId'] ?? get_the_ID();
			$post                             = get_post( $post_id );
			$component['children']            = \WP_Irving\Templates\convert_blocks_to_components( parse_blocks( $post->post_content ) );

			$component = \WP_Irving\Templates\handle_data_provider( $component );

			$component['name']                = 'irving/container';
			$component['config']['themeName'] = 'fullBleed';
			return $component;
		},
		'data_provider' => [
			'postId' => [
				'type' => 'integer',
			],
		],
	]
);

/**
 * Post content component.
 */
register_component(
	'post/tags',
	[
		'callback' => function( $component ) {

			// Use the data provider, or fallback to global.
			$post_id                          = $component['data_provider']['postId'] ?? get_the_ID();

			$component['children'] = array_map(
				function( $term ) {
					return [
						'name'     => 'irving/html',
						'children' => [
							sprintf(
								'<a href="%2$s">%1$s</a>',
								$term->name,
								get_term_link( $term )
							),
						],
					];
				},
				get_the_tags( $post_id )
			);

			$component['name'] = 'irving/passthrough';

			return $component;
		},
		'data_provider' => [
			'postId' => [
				'type' => 'integer',
			],
		],
	]
);

register_component(
	'bloginfo/name',
	[
		'callback' => function( $component ) {
			return get_bloginfo( 'name' );
		},
	]
);


register_component(
	'material/card-media',
	[
		'callback' => function( $component ) {
			$component['config']['image'] = get_the_post_thumbnail_url( $component['data_provider']['postId'] ?? 0 );
			return $component;
		},
	]
);

register_component(
	'core/paragraph',
	[
		'callback' => function( $component ) {
			$component['name'] = 'irving/html';
			return $component;
		},
	]
);

register_component(
	'irving/menu',
	[
		'callback' => function( $component ) {

			$menu_items = (array) wp_get_nav_menu_items(
				get_nav_menu_locations()[ $component['config']['slug'] ?? '' ] ?? 0
			);

			$component['children'] = array_map(
				function( \WP_Post $menu_item_post ) {
					return [
						'name' => 'material/menu-item',
						'children' => [
							[
								'name' => 'material/link',
								'config' => [
									'href' => esc_url( $menu_item_post->url ?? get_the_permalink( $menu_item_post ) ),
								],
								'children' => [
									esc_html( $menu_item_post->title ),
								],
							],
						],
					];
				},
				$menu_items
			);

			$component['name'] = 'irving/passthrough';

			return $component;
		},
	]
);

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

			$component['name'] = 'irving/passthrough';

			return $component;
		},
	]
);

register_component(
	'post/permalink',
	[
		'callback' => function( $component ) {

			// Use the data provider, or fallback to global.
			$post_id = $component['data_provider']['postId'] ?? get_the_ID();
			$component['config']['href'] = get_the_permalink( $post_id );
			$component['name']           = 'material/link';
			return $component;
		},
		'data_provider' => [
			'postId' => [
				'type' => 'integer',
			],
		],
	]
);

register_component(
	'post/excerpt',
	[
		'callback' => function( $component ) {
			$post_id = $component['data_provider']['postId'] ?? get_the_ID();

			return esc_html( substr( get_the_excerpt( $post_id ), 30 ) );
		},
	]
);
