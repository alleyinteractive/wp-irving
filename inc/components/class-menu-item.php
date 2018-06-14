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
