<?php
/**
 * Class file for Irving's Menu component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the components of the menu.
 */
class Menu extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'menu';

	/**
	 * Define a default config shape.
	 *
	 * @return array Default config values for this component.
	 */
	public function default_config() {
		return [
			'classNames' => [],
			'location'   => '',
			'title'      => '',
		];
	}

	/**
	 * Build a menu component by parsing a menu location.
	 *
	 * @param  string $menu_location Menu location.
	 * @return Menu An instance of the Menu class.
	 */
	public function parse_wp_menu_by_location( string $menu_location ) {

		$this->config['location'] = $menu_location;

		// Get menu locations.
		$locations = get_nav_menu_locations();

		// Get object id by location.
		$menu_term = wp_get_nav_menu_object( $locations[ $menu_location ] ?? null );

		// If valid menu term.
		if ( $menu_term instanceof \WP_Term ) {
			$this->parse_wp_menu( $menu_term );
		}

		return $this;
	}

	/**
	 * Build a menu component by parsing a menu.
	 *
	 * @param  \WP_Term $menu Menu term.
	 * @return Menu An instance of the Menu class.
	 */
	public function parse_wp_menu( \WP_Term $menu ) {
		$menu_items = wp_get_nav_menu_items( $menu );

		$this->build_menu( $this, $menu_items );

		return $this;
	}

	/**
	 * Recursive function to build a complete menu with children menu items.
	 *
	 * @param  Menu    $menu Instance of menu class.
	 * @param  array   $menu_items Menu items.
	 * @param  integer $parent_id  Parent menu ID.
	 * @return array All menu items.
	 */
	public function build_menu( $menu, $menu_items, $parent_id = 0 ) {
		// Loop through all menu items.
		foreach ( (array) $menu_items as $key => $menu_item ) {

			// Current menu's id.
			$menu_item_id = $menu_item->ID;

			// Current menu's parent id.
			$menu_item_parent_id = absint( $menu_item->menu_item_parent );

			// Is the current menu item a child of the parent item.
			if ( $menu_item_parent_id === $parent_id ) {

				// Get parsed menu item.
				$clean_menu_item = menu_item()->parse_menu_post( $menu_item );

				// Remove from loop.
				unset( $menu_items[ $key ] );

				// Normalize parent IDs for comparison.
				$parent_ids = array_map( 'absint', wp_list_pluck( $menu_items, 'menu_item_parent' ) );

				if ( in_array( $menu_item_id, $parent_ids, true ) ) {
					// Recursively build children menu items.
					$clean_menu_item->children[] = $this->build_menu( menu( 'menu',
						[
							'location' => 'submenu',
							'title'    => $menu_item->title,
						]
					), $menu_items, $menu_item_id );
				}

				$menu->children[] = $clean_menu_item;
			}
		}

		return (array) $menu;
	}
}

/**
 * Helper to get the component.
 *
 * @param  string $name     Component name or array of properties.
 * @param  array  $config   Component config.
 * @param  array  $children Component children.
 * @return Menu An instance of the Menu class.
 */
function menu( $name = '', array $config = [], array $children = [] ) {
	return new Menu( $name, $config, $children );
}
