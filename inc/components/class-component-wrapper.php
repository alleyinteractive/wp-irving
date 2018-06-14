<?php
/**
 * Parent class file for Irving's Component Wrapper.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Component Wrapper class.
 */
class Component_Wrapper extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'component-wrapper';


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
 * Helper to generate a generic component.
 *
 * @param  string $name     Component name or array of properties.
 * @param  array  $config   Component config.
 * @param  array  $children Component children.
 * @return Component An instance of the Component class.
 */
function component_wrapper( $name = '', array $config = [], array $children = [] ) {
	return new Component_Wrapper( $name, $config, $children );
}
