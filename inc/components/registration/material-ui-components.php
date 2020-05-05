<?php
/**
 * Registration for Material UI commponents.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Add the post thumbnail to a card.
 *
 * @return array
 */
register_component(
	'material/card-media',
	[
		'callback' => function( $component ) {
			$component['config']['image'] = get_the_post_thumbnail_url( $component['data_provider']['postId'] ?? 0 );
			return $component;
		},
	]
);

/**
 * Material UI menu, leveraging WordPress' menu API.
 *
 * @param string $comopnent['config']['slug'] Slug of the location of the menu.
 * @return arrray
 */
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
				array_filter( $menu_items ?? [] )
			);

			$component['name'] = '';

			return $component;
		},
	]
);
