<?php
/**
 * WordPress Menu.
 *
 * @package Irving_Components
 *
 * @todo Remove the Material UI part of this.
 *
 * @return array
 */

if ( ! function_exists( '\WP_Irving\Components\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
\WP_Irving\Components\get_registry()->register_component_from_config(
	__DIR__ . '/component',
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
