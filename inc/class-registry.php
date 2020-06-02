<?php
/**
 * Component registry.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

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
	 */
	public function register_component( string $name, array $args = [] ) {
		$this->components[ $name ] = $args;
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
	 * Register a component using a json config.
	 *
	 * @param string $config_path JSON config file.
	 * @param array  $args        Component args.
	 */
	public function register_component_from_config( string $config_path, array $args = [] ): bool {

		// Add the extension if necessary.
		if ( false === strpos( $config_path, '.json' ) ) {
			$config_path .= '.json';
		}

		// Validate the config file exists.
		if ( ! file_exists( $config_path ) ) {
			wp_die(
				sprintf(
					// Translators: %1$s: Error message, %2$s Template path.
					esc_html__( 'Error: Could not find component.json at %1$s. Double check the filename.', 'wp-irving' ),
					esc_html( $config_path )
				)
			);
			return false;
		}

		// Load and decode JSON component config.
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		$config = file_get_contents( $config_path );
		$config = json_decode( $config, true );

		// Validate config loaded and `name` is available.
		if ( is_null( $config ) || ! isset( $config['name'] ) ) {
			wp_die(
				sprintf(
					// Translators: %1$s: Error message, %2$s Template path.
					esc_html__( 'Error: %1$s found in %2$s.', 'wp-irving' ),
					esc_html( json_last_error_msg() ),
					esc_html( $config_path )
				)
			);
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
			$regex              = new \RegexIterator( $iterator, '/.+\/register\.php$/', \RecursiveRegexIterator::ALL_MATCHES );

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
