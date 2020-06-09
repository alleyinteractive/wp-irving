<?php
/**
 * Base component class.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use JsonSerializable;

/**
 * An object representing a component.
 */
class Component implements JsonSerializable {

	/**
	 * Name.
	 *
	 * Only supports a single `/` as a delimiter between the namespace and
	 * component name.
	 *
	 * @var string
	 */
	protected $name = 'wp-irving/component';

	/**
	 * Config.
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 * Config schema.
	 *
	 * @var array
	 */
	protected $config_schema = [];

	/**
	 * Default schema for any given config.
	 *
	 * @var array
	 */
	protected $config_schema_shape = [
		'default' => null,
		'hidden'  => false,
		'type'    => 'null',
	];

	/**
	 * Children.
	 *
	 * @var array
	 */
	protected $children = [];

	/**
	 * Theme name.
	 *
	 * @var string
	 */
	protected $theme = '';

	/**
	 * Theme options.
	 *
	 * @var array
	 */
	protected $theme_options = [];

	/**
	 * Map of provided context.
	 *
	 * @var array
	 */
	private $provides_context = [];

	/**
	 * Map of consumed context to config keys.
	 *
	 * @var array
	 */
	private $use_context = [];

	/**
	 * Calculated context values.
	 *
	 * @var array
	 */
	private $context = [];

	/**
	 * Callback function name.
	 *
	 * @var callable
	 */
	private $callback;

	/**
	 * Component constructor.
	 *
	 * @param string $name Component name.
	 * @param array  $args Possible constructor args.
	 */
	public function __construct( string $name, array $args = [] ) {

		// Set name.
		$this->set_name( $name );

		// Set default args.
		$args = wp_parse_args(
			$args,
			[
				'config'           => [],
				'config_schema'    => [],
				'children'         => [],
				'theme'            => 'default',
				'theme_options'    => [ 'default' ],
				'provides_context' => [],
				'use_context'      => [],
				'callback'         => null,
			]
		);

		// Set up config.
		$this->set_config( $args['config'] );

		// Set up config_schema.
		$this->set_config_schema( $args['config_schema'] );

		// Set up children.
		$this->set_children( $args['children'] );

		// Set up theme values.
		$this->set_theme_options( $args['theme_options'] );
		$this->set_theme( $args['theme'] );

		// Set up context values.
		$this->set_provides_context( $args['provides_context'] );
		$this->set_use_context( $args['use_context'] );

		// Set up callback.
		$this->set_callback( $args['callback'] );
	}

	/**
	 * Get the name.
	 *
	 * @return string Component name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get the namespace.
	 *
	 * If we don't have a valid namespace, return an empty string.
	 *
	 * @return string
	 */
	public function get_namespace(): string {

		// Get name.
		$name = $this->get_name();

		// If we don't have a slash, return an empty string.
		if ( false === strpos( $name, '/' ) ) {
			return '';
		}

		// Get the name parts.
		$parts = explode( '/', $this->get_name(), 2 );

		// Return the first part, or an empty string.
		return $parts[0] ?? '';
	}

	/**
	 * Set the component name.
	 *
	 * @param string $name New component name.
	 * @return self
	 */
	public function set_name( string $name ): self {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get a config value by key, or the entire config.
	 *
	 * @param string|null $key Key for the config array.
	 * @return mixed Null if the key isn't set.
	 */
	public function get_config( ?string $key = null ) {

		// If null, return the entire object.
		if ( is_null( $key ) ) {
			return $this->config;
		}

		return $this->get_config_by_key( $key );
	}

	/**
	 * Get a single config value by key.
	 *
	 * @param string $key Name of config key.
	 * @return mixed Null if the key isn't set.
	 */
	public function get_config_by_key( string $key ) {
		return $this->config[ $key ] ?? null;
	}

	/**
	 * Set a config value by key, or the entire config array.
	 *
	 * @param array|string $config_array_or_key Config array or key.
	 * @param mixed        $value               Config key value.
	 * @return self
	 */
	public function set_config( $config_array_or_key, $value = null ): self {

		// Set the entire config.
		if ( is_array( $config_array_or_key ) || is_object( $config_array_or_key ) ) {
			$this->config = (array) $config_array_or_key;
			return $this;
		}

		// Set a single value.
		$this->set_config_by_key( $config_array_or_key, $value );
		return $this;
	}

	/**
	 * Merge an array of config values into the existing config array.
	 *
	 * @param array $config Config array.
	 * @return self
	 */
	public function merge_config( array $config ): self {
		$this->config = array_merge( $this->config, $config );
		return $this;
	}

	/**
	 * Set a single config property.
	 *
	 * @param string $key   Config key name.
	 * @param mixed  $value Config key value.
	 * @return self
	 */
	public function set_config_by_key( string $key, $value ): self {
		$this->config[ $key ] = $value;
		return $this;
	}

	/**
	 * Get the config schema.
	 *
	 * @return array
	 */
	public function get_config_schema() {
		return $this->config_schema;
	}

	/**
	 * Set schema describing config.
	 *
	 * @param array $config_schema Schema array.
	 * @return self
	 */
	public function set_config_schema( array $config_schema ): self {

		// Ensure that every schema has all the expected properties.
		foreach ( $config_schema as $key => &$properties ) {
			$properties = wp_parse_args( $properties, $this->config_schema_shape );
		}

		$this->config_schema = $config_schema;
		return $this;
	}

	/**
	 * Get all children.
	 *
	 * @return array
	 */
	public function get_children(): array {
		return $this->children;
	}

	/**
	 * Set all children.
	 *
	 * @param array $children Children.
	 * @return self
	 */
	public function set_children( array $children ): self {
		$this->children = self::reset_array( $children );
		return $this;
	}

	/**
	 * Prepend children.
	 *
	 * @param array $children Children.
	 * @return self
	 */
	public function prepend_children( array $children ): self {
		return $this->set_children(
			array_merge(
				self::reset_array( $children ),
				$this->get_children()
			)
		);
	}

	/**
	 * Append children.
	 *
	 * @param array $children Children.
	 * @return self
	 */
	public function append_children( array $children ): self {
		return $this->set_children(
			array_merge(
				$this->get_children(),
				self::reset_array( $children )
			)
		);
	}

	/**
	 * Set all children using a non-array value.
	 *
	 * @param mixed $child Child.
	 * @return self
	 */
	public function set_child( $child ): self {
		return $this->set_children( [ $child ] );
	}

	/**
	 * Prepend child using a non-array value.
	 *
	 * @param mixed $child Child.
	 * @return self
	 */
	public function prepend_child( $child ): self {
		return $this->prepend_children( [ $child ] );
	}

	/**
	 * Append child using a non-array value.
	 *
	 * @param mixed $child Child.
	 * @return self
	 */
	public function append_child( $child ): self {
		return $this->append_children( [ $child ] );
	}

	/**
	 * Loop through this component's children, and convert any array with a `name` property into
	 *
	 * @param bool $recursive Also hydrate nested children.
	 * @return self
	 */
	public function hydrate_children( bool $recursive = true ): self {

		$children = $this->get_children();

		foreach ( $children as &$child ) {

			// Only look for arrays with a `name` key.
			if ( ! is_array( $child ) || ! isset( $child['name'] ) ) {
				continue;
			}

			// Replace the array with an initalized component instance.
			$child = new Component( $child['name'], $child );

			// Use this recursively.
			if ( $recursive ) {
				$child->hydrate_children();
			}
		}

		return $this->set_children( $children );
	}

	/**
	 * Sanitize an array of children by ensuring invalid values are removed and
	 * the index is reset.
	 *
	 * @param array $children Array of values to sanitize.
	 * @return array
	 */
	public static function reset_array( array $children ): array {
		return array_values( array_filter( $children ) );
	}

	/**
	 * Get the current theme.
	 *
	 * @return string
	 */
	public function get_theme(): string {
		return $this->theme;
	}

	/**
	 * Set the component theme.
	 *
	 * @param string $theme Theme name.
	 * @param bool   $force Optional. Ignore the theme options. Default false.
	 * @return self
	 */
	public function set_theme( string $theme, bool $force = false ): self {

		// If the theme is a valid option, or we're forcing, update the value.
		if (
			in_array( $theme, $this->get_theme_options(), true )
			|| $force
		) {
			$this->theme = $theme;
		}

		return $this;
	}

	/**
	 * Get all theme options.
	 *
	 * @return array
	 */
	public function get_theme_options(): array {
		return $this->theme_options;
	}

	/**
	 * Test if a string is in the theme options.
	 *
	 * @param string $theme Theme name.
	 * @return bool
	 */
	public function is_available_theme( string $theme ): bool {
		return in_array( $theme, $this->get_theme_options(), true );
	}

	/**
	 * Set theme options.
	 *
	 * @param array $theme_options New theme options. Ensures uniques.
	 * @return self
	 */
	public function set_theme_options( array $theme_options ): self {
		$this->theme_options = array_unique( $theme_options );
		return $this;
	}

	/**
	 * Add theme options.
	 *
	 * @param array $theme_options One or more theme names to add.
	 * @return self
	 */
	public function add_theme_options( array $theme_options ): self {

		array_map(
			function( $theme_option ) {

				// Ignore non-strings for now.
				if ( ! is_string( $theme_option ) ) {
					return;
				}

				$this->add_theme_option( $theme_option );
			},
			$theme_options
		);

		return $this;
	}

	/**
	 * Add a theme option.
	 *
	 * @param string $theme_option Theme name.
	 * @return self
	 */
	public function add_theme_option( string $theme_option ): self {

		// Ensure it's not already an option.
		if ( ! $this->is_available_theme( $theme_option ) ) {
			$this->theme_options[] = $theme_option;
		}

		return $this;
	}

	/**
	 * Remove theme options.
	 *
	 * @param array $theme_options One or more themes names to remove.
	 * @return self
	 */
	public function remove_theme_options( array $theme_options ): self {

		array_map(
			function( $theme_option ) {

				// Ignore non-strings for now.
				if ( ! is_string( $theme_option ) ) {
					return;
				}

				$this->remove_theme_option( $theme_option );
			},
			$theme_options
		);

		return $this;
	}

	/**
	 * Remove theme option.
	 *
	 * @param string $theme_option Theme name.
	 * @return [type]               [description]
	 */
	public function remove_theme_option( string $theme_option ): self {

		// Ensure it's not already an option.
		if ( $this->is_available_theme( $theme_option ) ) {

			// Loop through options, removing the correct key.
			$theme_options = $this->get_theme_options();
			foreach ( $theme_options as $index => $key ) {
				if ( $key === $theme_option ) {
					unset( $theme_options[ $index ] );
					break;
				}
			}

			// Reset the array every time.
			$this->set_theme_options(
				self::reset_array( $theme_options )
			);
		}

		return $this;
	}

	/**
	 * Get the context provider.
	 *
	 * @return array
	 */
	public function get_provides_context(): array {
		return $this->provides_context;
	}

	/**
	 * Set the context providers.
	 *
	 * @param array $provides_context Context provider.
	 * @return self
	 */
	public function set_provides_context( array $provides_context ): self {
		$this->provides_context = $provides_context;
		return $this;
	}

	/**
	 * Get the use context map.
	 *
	 * @return array
	 */
	public function get_use_context(): array {
		return $this->use_context;
	}

	/**
	 * Set the use context map.
	 *
	 * @param array $use_context Context consumer.
	 * @return self
	 */
	public function set_use_context( array $use_context ): self {
		$this->use_context = $use_context;
		return $this;
	}

	/**
	 * Returns an array of calculated context values.
	 *
	 * @return array
	 */
	public function get_context(): array {
		return $this->context;
	}

	/**
	 * Sets up context values from a context store.
	 *
	 * @param Context_Store $store A context store instance.
	 * @return self
	 */
	public function use_context( Context_Store $store ): self {

		foreach ( $this->get_use_context() as $key => $config ) {
			// Store context values internally so they're available in callbacks.
			$this->context[ $key ] = $store->get( $key );

			// Apply context values to config keys if the context
			// value is set and the mapped config key exists as is not empty.
			if (
				null !== $store->get( $key ) &&
				empty( $this->get_config_by_key( $key ) )
			) {
				$this->set_config_by_key( $config, $store->get( $key ) );
			}
		}

		return $this;
	}

	/**
	 * Passes context values to a context store.
	 *
	 * @param Context_Store $store A context store instance.
	 * @return self
	 */
	public function provide_context( Context_Store $store ): self {
		$context = [];

		foreach ( $this->get_provides_context() as $key => $config ) {
			$context[ $key ] = $this->get_config_by_key( $config );
		}

		// Always provide context when called or else
		// Resetting won't stay in sync.
		$store->set( $context );

		return $this;
	}

	/**
	 * Sets the callback property if passed a callable.
	 *
	 * @param string|array $callback A callable function.
	 * @return self
	 */
	private function set_callback( $callback ): self {
		if ( is_callable( $callback ) ) {
			$this->callback = $callback;
		}

		return $this;
	}

	/**
	 * Return the value of the callback property.
	 *
	 * @return callable|null A callable in either a string or array syntax.
	 *                       Returns null if not set.
	 */
	public function get_callback() {
		return $this->callback;
	}

	/**
	 * Execute a hydration callback.
	 *
	 * @return self
	 */
	public function do_callback(): self {
		if ( is_callable( $this->get_callback() ) ) {
			$this->callback( $this->get_callback() );
		}

		return $this;
	}

	/**
	 * Run a user callback on this class. This can be used to create a fork in
	 * the method chain.
	 *
	 * @param callable $callable Callable.
	 * @param mixed    ...$args  Optional args to pass to the callback.
	 * @return Component
	 */
	public function callback( callable $callable, ...$args ) {
		return call_user_func_array( $callable, array_merge( [ &$this ], $args ) );
	}

	/**
	 * Convert all array keys to camel case.
	 *
	 * @param array $array Array to convert.
	 * @return array Updated array with camel-cased keys.
	 */
	public static function camel_case_keys( $array ): array {

		// Setup for recursion.
		$camel_case_array = [];

		// Loop through each key.
		foreach ( $array as $key => $value ) {

			if ( is_array( $value ) ) {
				$value = self::camel_case_keys( $value );
			}

			// Camel case the key.
			$new_key = self::camel_case( $key );

			$camel_case_array[ $new_key ] = $value;
		}

		return $camel_case_array;
	}

	/**
	 * Camel case a string.
	 *
	 * @param string $string String to camel case.
	 * @return string
	 */
	public static function camel_case( string $string ): string {

		if ( empty( $string ) ) {
			return $string;
		}

		// Replace dashes and spaces with underscores.
		$string = str_replace( '-', '_', $string );
		$string = str_replace( ' ', '_', $string );

		// Explode each part by underscore.
		$words = explode( '_', $string );

		$words = array_filter( $words );

		// Capitalize each key part.
		array_walk(
			$words,
			function( &$word ) {
				$word = ucwords( strtolower( $word ) );
			}
		);

		// Reassemble key.
		$string = implode( '', $words );

		// Lowercase the first character.
		$string[0] = strtolower( $string[0] );

		return $string;
	}

	/**
	 * Use `to_array()` method when component is serialized.
	 *
	 * @return array
	 */
	public function jsonSerialize() {

		/**
		 * Filter component object before serialization.
		 *
		 * @param Component $this Current component instance.
		 */
		apply_filters( 'wp_irving_serialize_component', $this );

		/**
		 * Filter component array after serialization.
		 *
		 * @param array $component_as_array Current component instance.
		 */
		return apply_filters( 'wp_irving_serialize_component_array', $this->to_array() );
	}

	/**
	 * Convert the class to an array.
	 *
	 * @return array
	 */
	public function to_array(): array {

		// Add the theme name to the config as Irving core expects.
		$this->set_config( 'theme_name', self::camel_case( $this->get_theme() ) );
		$this->set_config( 'theme_options', array_keys( $this->camel_case_keys( array_flip( $this->get_theme_options() ) ) ) );

		// Null any config keys where the config schema has hidden = true.
		foreach ( $this->get_config_schema() as $key => $config_schema ) {
			if ( $config_schema['hidden'] ) {
				$this->set_config( $key, null );
			}
		}

		return [
			'name'     => $this->get_name(),
			'config'   => (object) $this->camel_case_keys( $this->get_config() ),
			'children' => $this->get_children(),
		];
	}
}
