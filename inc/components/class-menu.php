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
	 * @return array Default config.
	 */
	public function default_config() {
		return [
			'classes' => [],
		];
	}

	/**
	 * Build a menu component by parsing a menu location.
	 *
	 * @param  string $menu_location Menu location.
	 * @return Menu An instance of the Menu class.
	 */
	public function parse_wp_menu_by_location( string $menu_location ) {
		return $this;
	}

	/**
	 * Build a menu component by parsing a menu.
	 *
	 * @param  string $menu_slug Menu slug.
	 * @return Menu An instance of the Menu class.
	 */
	public function parse_wp_menu( string $menu_slug ) {
		$menu_items = wp_get_nav_menu_items( $menu_slug );

		return $this;
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
