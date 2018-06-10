<?php
/**
 * Parent class file for Irving's Components.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the general component class.
 */
class Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Component config.
	 *
	 * @var array
	 */
	public $config = [];

	/**
	 * Component children.
	 *
	 * @var array
	 */
	public $children = [];

	/**
	 * Component constructor.
	 *
	 * @param string $name     Unique component slug or array of name, config,
	 *                         and children value.
	 * @param array  $config   Component config.
	 * @param array  $children Component children.
	 */
	public function __construct( $name = '', array $config = [], array $children = [] ) {

		// Allow $name to be passed as a config array.
		if ( is_array( $name ) ) {
			$data     = $name;
			$name     = $data['name'] ?? '';
			$config   = $data['config'] ?? [];
			$children = $data['children'] ?? [];
		}

		// Store in class vars unless overridden by extended classes.
		$this->name     = ! empty( $this->name ) ?  $this->name : $name;
		$this->config   = ! empty( $this->config ) ? $this->config : $config;
		$this->children = ! empty( $this->children ) ? $this->children : $children;
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
function component( $name = '', array $config = [], array $children = [] ) {
	return new Component( $name, $config, $children );
}
