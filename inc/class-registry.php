<?php
/**
 * Component registry.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Get an instance of the registry class.
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
 * Auto load some components which will then get registered. Defaults to the
 * theme directory and WP Irving plugin.
 */
function auto_register_components() {

	/**
	 * Filter the directories from which components should be autoloaded.
	 *
	 * @param array $directories Array of directories.
	 */
	$directories = apply_filters(
		'wp_irving_component_registry_directories',
		[
			'wp_irving' => WP_IRVING_PATH . '/inc/components/', // Load components from WP Irving.
			'theme'     => get_stylesheet_directory() . '/components/', // Load components from the activated theme.
		]
	);

	get_registry()->load_components( $directories );
}
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
	 * Get all registered components.
	 *
	 * @return array
	 */
	public function get_registered_components(): array {
		return $this->components;
	}

	/**
	 * Get a single registered component by name.
	 *
	 * @param string $name Component name.
	 * @return array
	 */
	public function get_registered_component( string $name ): ?array {
		return $this->components[ $name ] ?? null;
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
	 * Register a component using a json config.
	 *
	 * @param string $json_file JSON config file.
	 * @param array  $args      Component args.
	 */
	public function register_component_from_config( string $json_file, array $args = [] ): bool {

		// Add the extension if necessary.
		if ( false === strpos( $json_file, '.json' ) ) {
			$json_file .= '.json';
		}

		// Validate the config file exists.
		if ( ! file_exists( $json_file ) ) {
			return false;
		}

		// Load and decode JSON component config.
		$config = file_get_contents( $json_file );
		$config = json_decode( $config, true );

		// Validate config loaded and `name` is available.
		if ( is_null( $config ) || ! isset( $config['name'] ) ) {
			return false;
		}

		$this->register_component(
			$config['name'],
			array_merge_recursive( $config, $args )
		);

		return true;
	}

	/**
	 * Loop through some directories importing components and registering them.
	 *
	 * @param array $directories Directoriess to recursively loop through and
	 *                           load from.
	 */
	public function load_components( array $directories = [] ) {

		foreach ( array_values( $directories ) as $path ) {

			if ( ! is_dir( $path ) ) {
				continue;
			}

			// Recursively loop through $path, including anything that ends in index.php.
			$directory_iterator = new \RecursiveDirectoryIterator( $path );
			$iterator           = new \RecursiveIteratorIterator( $directory_iterator );
			$regex              = new \RegexIterator( $iterator, '/.+\/index\.php$/', \RecursiveRegexIterator::ALL_MATCHES );

			// Include each index.php entry point.
			foreach ( $regex as $file_path ) {
				$file_path = $file_path[0][0] ?? '';
				if ( file_exists( $file_path ) ) {
					include_once $file_path;
				}
			}
		}
	}
}
