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
	 * @return array|null The registered component, or null.
	 */
	public function get_registered_component( string $name ): ?array {
		return $this->components[ $name ] ?? null;
	}

	/**
	 * Register a component.
	 *
	 * @param string $name Component name.
	 * @param array  $args Component args.
	 * @return bool Returns true on success.
	 */
	public function register_component( string $name, array $args = [] ) {
		if ( empty( $args['name'] ) ) {
			$args['name'] = $name;
		}

		$this->components[ $name ] = $args;

		return true;
	}

	/**
	 * Remove a component from the registry.
	 *
	 * @param string $name The name of the component to remove.
	 * @return bool Returns true on success, false on failure.
	 */
	public function unregister_component( string $name ) {
		if ( ! isset( $this->components[ $name ] ) ) {
			return false;
		}

		unset( $this->components[ $name ] );
		return true;
	}

	/**
	 * Remove all registered components from the registry.
	 *
	 * @return Registry
	 */
	public function reset(): self {
		$this->components = [];

		return $this;
	}

	/**
	 * Loop through some directories importing components and registering them.
	 *
	 * @param array $directories Directories to recursively loop through and
	 *                           load from.
	 */
	public function load_components( array $directories = [] ) {

		// Ensure we're not duplicating our efforts.
		$directories = array_unique( array_values( $directories ) );

		foreach ( $directories as $path ) {

			if ( ! is_dir( $path ) ) {
				continue;
			}

			// Recursively loop through $path, including anything that ends in index.php.
			$directory_iterator = new \RecursiveDirectoryIterator( $path );
			$iterator           = new \RecursiveIteratorIterator( $directory_iterator );
			$regex              = new \RegexIterator( $iterator, '/.+\/component\.php$/', \RecursiveRegexIterator::ALL_MATCHES );

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
