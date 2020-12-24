<?php
/**
 * Menu.
 *
 * Output links as menu items.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Post;
use WP_Term;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback'   => function ( array $config ): array {

			// Get menu ID (int) for the selected location.
			$config['menu_id'] = get_nav_menu_locations()[ $config['location'] ] ?? 0;

			if ( ! $config['menu_id'] ) {
				return $config;
			}

			$menu_object = wp_get_nav_menu_object( $config['menu_id'] );

			// Invalid menu.
			if ( ! $menu_object instanceof WP_Term ) {
				return $config;
			}

			// Use the Menu Object name if not declared explicitly.
			if ( empty( $config['menu_name'] ) && isset( $menu_object->name ) ) {
				$config['menu_name'] = html_entity_decode( $menu_object->name );
			}

			return $config;
		},
		'children_callback' => function ( array $children, array $config ): array {

			$menu_id = $config['menu_id'];

			// Bail early if no menu is found.
			if ( ! $menu_id ) {
				return $children;
			}

			$menu_items = wp_get_nav_menu_items( $config['menu_id'] );

			/*
			 * Sort the menu items into two groups: top level items, and a list
			 * of sub-menu items organized by parent ID. Then we can recursively
			 * convert top level items to components, and pass the list of sub-menu
			 * items as possible children to be resolved and removed from the list.
			 */
			$top_level_items = [];
			$sub_menu_items  = [];

			foreach ( $menu_items as $menu_item ) {
				if ( ! $menu_item->menu_item_parent ) {
					$top_level_items[] = $menu_item;
				} else {
					$sub_menu_items[ $menu_item->menu_item_parent ][] = $menu_item;
				}
			}

			foreach ( $top_level_items as $item ) {
				$children[] = convert_menu_to_components( $item, $sub_menu_items );
			}

			return $children;
		},
	]
);


/**
 * Convert a WP nav menu item into a component.
 *
 * Children are passed by reference to make recursive calls more performant.
 *
 * @param WP_Post $menu_item A menu item post object.
 * @param array   $children  Optional. Array of possible children to match with parents.
 * @return array
 */
function convert_menu_to_components( WP_Post $menu_item, &$children = [] ) {

	// Convert the menu class instance into a simpler array format.
	$component = new Component(
		'irving/menu-item',
		[
			'config' => [
				'attribute_title' => (string) $menu_item->attr_title,
				'classes'         => array_filter( (array) $menu_item->classes ),
				'id'              => absint( $menu_item->ID ),
				'parent_id'       => absint( $menu_item->menu_item_parent ),
				'target'          => (string) $menu_item->target,
				'title'           => html_entity_decode( (string) $menu_item->title ),
				'url'             => (string) $menu_item->url,
			],
		]
	);

	if ( isset( $children[ $menu_item->ID ] ) ) {
		foreach ( $children[ $menu_item->ID ] as $child ) {
			$component->append_child( convert_menu_to_components( $child, $children ) );
		}

		// Remove children of this item once they're converted.
		unset( $children[ $menu_item->ID ] );
	}

	return $component;
}
