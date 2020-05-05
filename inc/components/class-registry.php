<?php
/**
 * Component registry.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Registry.
 */
class Registry {

	/**
	 * Array of registered components.
	 *
	 * @var array
	 */
	protected $components = [];

	/**
	 * Class instance.
	 *
	 * @var null|self
	 */
	protected static $instance;

	/**
	 * Get class instance
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
			static::$instance->setup();
		}
		return static::$instance;
	}

	/**
	 * Setup the singleton.
	 */
	public function setup() {
		// Nothing to setup. Just a placeholder for now.
	}

	/**
	 * Register a component.
	 *
	 * @param string $name Component name.
	 * @param array  $args Component args.
	 */
	public function register_component( string $name, array $args = [] ) {
		$this->components[ $name ] = $args;
	}

	/**
	 * Get all registered components.
	 *
	 * @return array
	 */
	public function get_registered_components() {
		return $this->components;
	}

	/**
	 * Get a single registered component by name.
	 *
	 * @param string $name Component name.
	 * @return array
	 */
	public function get_registered_component( string $name ) {
		return $this->components[ $name ] ?? null;
	}
}

/**
 * Get the registry object/instance.
 *
 * @return \WP_Irving\Components\Registry
 */
function get_registry() {
	return Registry::instance();
}

/**
 * Register a component.
 *
 * @param string $name Component name.
 * @param array  $args Component args.
 */
function register_component( string $name, $args = [] ) {
	get_registry()->register_component( $name, $args );
}

/**
 * Get a single registered component by name.
 *
 * @param string $name Component name.
 * @return array
 */
function get_registered_component( string $name ) {
	return get_registry()->get_registered_component( $name );
}

/**
 * Get all registered components.
 *
 * @return array
 */
function get_registered_components() {
	return get_registry()->get_registered_components();
}
