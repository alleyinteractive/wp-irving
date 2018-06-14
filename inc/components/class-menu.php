<?php
/**
 * Class file for Irving's Menu Item component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the components of the Menu Item.
 */
class Menu_Item extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'menu-item';

	/**
	 * Define a default config shape.
	 *
	 * @return array Default config.
	 */
	public function default_config() {
		return [
			'label' => '',
			'url'   => '',
		];
	}
}

/**
 * Helper to get the component.
 *
 * @param  string $name     Component name or array of properties.
 * @param  array  $config   Component config.
 * @param  array  $children Component children.
 * @return Menu_Item An instance of the Menu_Item class.
 */
function menu_item( $name = '', array $config = [], array $children = [] ) {
	return new Menu_Item( $name, $config, $children );
}
