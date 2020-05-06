<?php
/**
 * Component registry.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

add_filter( 'init', __NAMESPACE__ . '\auto_register_components' );

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
 * Loop through some directories importing components and registering them.
 */
function auto_register_components() {

	$auto_load_directories = [
		get_stylesheet_directory() . '/components/', // Load from the theme.
		// Load from node modules?
		// Load from root wp-content folder?
	];

	foreach ( $auto_load_directories as $path ) {

		if ( ! is_dir( $path ) ) {
			continue;
		}

		// Recursively loop through $path, including anything that ends in index.php.
		$directory_iterator = new \RecursiveDirectoryIterator( $path );
		$iterator           = new \RecursiveIteratorIterator( $directory_iterator );
		$regex              = new \RegexIterator( $iterator, '/.+\/index\.php$/', \RecursiveRegexIterator::ALL_MATCHES );

		foreach ( $regex as $file_path ) {
			if ( file_exists( $file_path[0][0] ) ) {
				include_once $file_path[0][0];
			}
		}
	}
}

/**
 * Get the registry object/instance.
 *
 * @return \WP_Irving\Components\Registry
 */
function get_registry() {

	static $registry;

	if ( empty( $registry ) ) {
		$registry = new Registry();
	}

	return $registry;
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
 * Register a component using a json config.
 *
 * @param string $file JSON config file.
 * @param array  $args Component args.
 */
function register_component_from_config( string $file, array $args = [] ) {

	$file .= '.json';

	if ( ! file_exists( $file ) ) {
		return false;
	}

	// Load and decode JSON component config.
	$config = file_get_contents( $file );
	$config = json_decode( $config, true );

	// Validate config loaded and `name` is available.
	if ( is_null( $config ) || ! isset( $config['name'] ) ) {
		return false;
	}

	get_registry()->register_component(
		$config['name'],
		array_merge_recursive( $config, $args )
	);

	return true;
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
