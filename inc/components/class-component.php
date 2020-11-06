<?php
/**
 * Base component class.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use JsonSerializable;
use WP_Irving\Templates;

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
	 * Alias.
	 *
	 * Setup an alias for this component to inherit the config from another
	 * component.
	 *
	 * @var string
	 */
	protected $alias = '';

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
	protected $schema = [];

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
	 * Config hydration callback.
	 *
	 * Function will receive the current configuration values and
	 * should return a full array of hydrated configuration values.
	 *
	 * @var callable
	 */
	private $config_callback;

	/**
	 * Callback for setting up children.
	 *
	 * Function should return an array of children components.
	 * Runs after configuration is hydrated and context is provided.
	 *
	 * @var callable
	 */
	private $children_callback;

	/**
	 * Callback for setting visibility.
	 *
	 * Callback should return a boolean value. When the callback returns false,
	 * the component and it's children will be removed during serialization.
	 *
	 * @var callable
	 */
	private $visibility_callback;

	/**
	 * Component constructor.
	 *
	 * @param string $name Component name.
	 * @param array  $args Possible constructor args.
	 */
	public function __construct( string $name, array $args = [] ) {
		return $this
			->set_name( $name )
			->setup_schema()
			->process_args( $args );
	}

	/**
	 * Set properties from registered configuration.
	 *
	 * @return self
	 */
	private function setup_schema(): self {
		// Supported schema with default values.
		$schema_defaults = [
			'_alias'              => '',
			'config'              => [],
			'theme_options'       => [ 'default' ],
			'provides_context'    => [],
			'use_context'         => [],
			'config_callback'     => null,
			'children_callback'   => null,
			'visibility_callback' => null, // @todo implement.
		];

		$schema = get_registry()->get_registered_component( $this->name ) ?: [];

		$schema = wp_parse_args(
			array_intersect_key( $schema, $schema_defaults ),
			$schema_defaults
		);

		$this->set_alias( $schema['_alias'] );
		$this->set_schema( $schema['config'] );
		$this->set_theme_options( $schema['theme_options'] );
		$this->set_provides_context( $schema['provides_context'] );
		$this->set_use_context( $schema['use_context'] );
		$this->set_callback( 'config', $schema['config_callback'] );
		$this->set_callback( 'children', $schema['children_callback'] );
		$this->set_callback( 'visibility', $schema['visibility_callback'] );

		return $this;
	}

	/**
	 * Set up properties from arguments.
	 *
	 * @param array $args An array of user passed component arguments.
	 * @return self
	 */
	private function process_args( array $args ): self {

		// Set default args.
		$args = wp_parse_args(
			$args,
			[
				'config'      => [],
				'children'    => [],
				'use_context' => [],
				'theme'       => 'default',
			]
		);

		$this
			->add_use_context( $args['use_context'] )
			->apply_context()
			->set_config( $args['config'] )
			->hydrate_config()
			->set_theme( $args['theme'] ) // @todo, should this just be config?
			->set_children( $args['children'] );

		return $this;
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
	private function set_name( string $name ): self {
		$this->name = $name;
		return $this;
	}

	/**
	 * Set the alias.
	 *
	 * @param string $alias The alias to set.
	 * @return self
	 */
	private function set_alias( string $alias ): self {
		$this->alias = $alias;

		return $this;
	}

	/**
	 * Get the alias.
	 *
	 * @return string Component alias.
	 */
	public function get_alias(): string {
		return $this->alias;
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
	 * Set config from an array of values.
	 *
	 * @param array $config Config array.
	 * @return self
	 */
	private function set_config( array $config ): self {
		// Set up defaults first.
		foreach ( $this->get_schema() as $schema_key => $schema ) {
			if (
				empty( $this->get_config_by_key( $schema_key ) ) &&
				isset( $schema['default'] )
			) {
				$this->set_config_value( $schema_key, $schema['default'] );
			}
		}

		// Set passed config.
		foreach ( $config as $config_key => $value ) {
			$this->set_config_value( $config_key, $value );
		}

		return $this;
	}

	/**
	 * Set a single config property.
	 *
	 * @param string $key   Config key name.
	 * @param mixed  $value Config key value.
	 * @return self
	 */
	private function set_config_value( string $key, $value ): self {

		$type_callbacks = [
			'array'  => 'is_array',
			'bool'   => 'is_bool',
			'int'    => 'is_int',
			'number' => 'is_numeric',
			'object' => 'is_object',
			'string' => 'is_string',
		];

		// Assume all values are valid unless a registered schema is present.
		$is_valid = true;

		$schema = $this->get_schema()[ $key ] ?? false;

		if (
			$schema &&
			isset( $schema['type'] ) &&
			isset( $type_callbacks[ $schema['type'] ] )
		) {
			$is_valid = call_user_func( $type_callbacks[ $schema['type'] ], $value );
		}

		// @todo add error handling.
		if ( $is_valid ) {
			$this->config[ $key ] = $value;
		}

		return $this;
	}

	/**
	 * Set schema for the component's config values.
	 *
	 * @param array $schema Config schema to set.
	 * @return self
	 */
	private function set_schema( array $schema ): self {
		// Apply the default schema shape to each config value.
		$parsed_schema = array_map(
			function( $config ) {
				return wp_parse_args(
					$config,
					[
						'default' => null,
						'hidden'  => false,
						'type'    => 'null',
					]
				);
			},
			$schema
		);

		$this->schema = $parsed_schema;

		return $this;
	}

	/**
	 * Get the component schema.
	 *
	 * @return array
	 */
	private function get_schema() {
		return $this->schema;
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
		// Set context before setting children.
		$this->provide_context();

		foreach ( $children as $key => &$child ) {

			// Consider marking this as _doing_it_wrong.
			if ( $child instanceof Component ) {
				continue;
			}

			// Handle template parts.
			if (
				isset( $child['name'] ) &&
				0 === strpos( $child['name'], 'template-part' )
			) {
				$template_data = Templates\hydrate_template_parts( $child );
				array_splice( $children, $key, 1, $template_data );
				continue;
			}

			// Convert argument syntax to template syntax.
			if ( is_array( $child ) && ! isset( $child['name'] ) ) {
				$child = array_merge(
					[
						'name' => $child[0],
					],
					$child[1] ?? []
				);
			}
		}

		// Run registered children_callback functions.
		if ( is_callable( $this->children_callback ) ) {
			$children = call_user_func_array( $this->children_callback, [ $children, $this->config ] );
		}

		// Hydrate children.
		foreach ( $children as &$child ) {
			// Let strings be strings.
			if ( ! is_array( $child ) ) {
				continue;
			}

			// Replace the array with an initialized component instance.
			$child = new Component( $child['name'], $child );
		}

		/**
		 * Filter the children values after the callback possibly fires.
		 *
		 * @param array  $children Children for this component.
		 * @param array  $config   Config for this component.
		 * @param string $name     Name of this component.
		 */
		$children = apply_filters( 'wp_irving_component_children', $children, $this->config, $this->get_name() );

		// Set children.
		$this->children = $this->reset_array( $children );

		// Reset context after setting children.
		$this->reset_context();

		return $this;
	}

	/**
	 * Set a single child component.
	 *
	 * @param mixed $child The child to add. Accepts string, array, or Component.
	 * @return self
	 */
	public function set_child( $child ): self {
		if ( is_string( $child ) ) {
			$child = [ $child ];
		}

		return $this->set_children( [ $child ] );
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
	 * Prepend child using a non-array value.
	 *
	 * @param mixed $child Child.
	 * @return self
	 */
	public function prepend_child( $child ): self {
		return $this->prepend_children( [ $child ] );
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
	 * Append child using a non-array value.
	 *
	 * @param mixed $child Child.
	 * @return self
	 */
	public function append_child( $child ): self {
		return $this->append_children( [ $child ] );
	}

	/**
	 * Runs a config callback to dynamically hydrate configuration values.
	 *
	 * Runs after context values have been applied.
	 *
	 * @return self
	 */
	private function hydrate_config(): self {
		/**
		 * Filter the config values before the config callback fires.
		 *
		 * @param array  $config Config for this component.
		 * @param string $name   Name of this component.
		 */
		$new_config = apply_filters( 'wp_irving_component_config_pre_hydration', $this->get_config(), $this->get_name() );

		if ( is_callable( $this->config_callback ) ) {
			$new_config = call_user_func_array( $this->config_callback, [ $new_config ] );
		}

		/**
		 * Filter the config values after the config callback fires.
		 *
		 * Note: This filter uses `after_hydration` instead of `post_hydration`
		 * to avoid confusion with `post` in the WordPress context.
		 *
		 * @param array  $config Config for this component.
		 * @param string $name   Name of this component.
		 */
		$new_config = apply_filters( 'wp_irving_component_config_after_hydration', $new_config, $this->get_name() );

		// @todo Add error handling.
		if ( is_array( $new_config ) ) {
			$this->set_config( $new_config );
		}

		return $this;
	}

	/**
	 * Loop through this component's children, instantiate and hydrate Component classes for each.
	 *
	 * @return self
	 */
	private function hydrate_children() {

		$children = $this->get_children();

		return $this->set_children( $children );
	}

	/**
	 * Sanitize an array of children by ensuring invalid values are removed and
	 * the index is reset.
	 *
	 * @param array $children Array of values to sanitize.
	 * @return array
	 */
	private function reset_array( array $children ): array {
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
	 * @return self
	 */
	private function set_theme( string $theme ): self {
		$this->theme = $theme;

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
	private function set_theme_options( array $theme_options ): self {
		$this->theme_options = array_unique( $theme_options );
		return $this;
	}

	/**
	 * Add theme options.
	 *
	 * @param array $theme_options One or more theme names to add.
	 * @return self
	 */
	private function add_theme_options( array $theme_options ): self {

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
	private function add_theme_option( string $theme_option ): self {

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
	private function remove_theme_options( array $theme_options ): self {

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
	private function remove_theme_option( string $theme_option ): self {

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
				$this->reset_array( $theme_options )
			);
		}

		return $this;
	}

	/**
	 * Get the context provider.
	 *
	 * @return array
	 */
	private function get_provides_context(): array {
		return $this->provides_context;
	}

	/**
	 * Set the context providers.
	 *
	 * @param array $provides_context Context provider.
	 * @return self
	 */
	private function set_provides_context( array $provides_context ): self {
		$this->provides_context = $provides_context;
		return $this;
	}

	/**
	 * Get the use context map.
	 *
	 * @return array
	 */
	private function get_use_context(): array {
		return $this->use_context;
	}

	/**
	 * Set the use context map.
	 *
	 * @param array $use_context Context consumer.
	 * @return self
	 */
	private function set_use_context( array $use_context ): self {
		$this->use_context = $use_context;
		return $this;
	}

	/**
	 * Add more entries to the use context map without overwriting old ones.
	 *
	 * @param array $use_context Context consumer.
	 * @return self
	 */
	private function add_use_context( array $use_context ): self {
		$this->set_use_context( array_merge( $this->get_use_context(), $use_context ) );
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
	 * @return self
	 */
	private function apply_context(): self {
		$store = get_context_store();

		foreach ( $this->get_use_context() as $key => $config ) {
			// Store context values internally so they're available in callbacks.
			$this->context[ $key ] = $store->get( $key );

			// Apply context values to config keys if set.
			if ( null !== $store->get( $key ) ) {
				if ( is_array( $config ) ) {
					// Handle destructuring object properties
					// to specific config values first.
					$obj = $store->get( $key );

					foreach ( $config as $prop => $value ) {
						$this->set_config_value( $value, $obj->$prop );
					}
				} else {
					// Set simple string-based values.
					$this->set_config_value( $config, $store->get( $key ) );
				}
			}
		}

		return $this;
	}

	/**
	 * Passes context values to a context store.
	 *
	 * @return self
	 */
	private function provide_context(): self {
		$context = [];

		foreach ( $this->get_provides_context() as $key => $config ) {
			$context[ $key ] = $this->get_config_by_key( $config );
		}

		// Always provide context when called or else
		// Resetting won't stay in sync.
		get_context_store()->set( $context );

		return $this;
	}

	/**
	 * Reset context to the state it was in before hydration.
	 *
	 * @return self
	 */
	private function reset_context(): self {
		get_context_store()->reset();

		return $this;
	}

	/**
	 * Sets the callback property if passed a callable.
	 *
	 * @param string       $type     Type of callback being set.
	 * @param string|array $callback A callable function.
	 * @return self
	 */
	private function set_callback( $type, $callback ): self {
		if ( is_callable( $callback ) ) {
			switch ( $type ) {
				case 'config':
					$this->config_callback = $callback;
					break;
				case 'children':
					$this->children_callback = $callback;
					break;
				case 'visibility':
					$this->visibility_callback = $callback;
					break;
				default:
					break;
			}
		}

		return $this;
	}

	/**
	 * Return the value of the callback property.
	 *
	 * @param string $type The type of callback to return; config, children, or visibility.
	 * @return callable|null A callable in either a string or array syntax.
	 *                       Returns null if not set.
	 */
	public function get_callback( string $type ): ?callable {
		switch ( $type ) {
			case 'config':
				$callback = $this->config_callback;
				break;
			case 'children':
				$callback = $this->children_callback;
				break;
			case 'visibility':
				$callback = $this->visibility_callback;
				break;
			default:
				$callback = null;
				break;
		}

		return $callback;
	}

	/**
	 * Convert all array keys to camel case.
	 *
	 * @param array $array Array to convert.
	 * @return array Updated array with camel-cased keys.
	 */
	private function camel_case_keys( $array ): array {

		// Setup for recursion.
		$camel_case_array = [];

		// Loop through each key.
		foreach ( $array as $key => $value ) {

			// If it's an array, and its inner keys should be camel cased, recurse a level deeper.
			if (
				is_array( $value )
				&& false !== ( $this->get_schema()[ $key ]['camel_case_inner_keys'] ?? true )
			) {
					$value = self::camel_case_keys( $value );
			}

			// Camel case the key unless the schema explicitly states otherwise.
			if ( false !== ( $this->get_schema()[ $key ]['camel_case_config_key'] ?? true ) ) {
				$new_key = self::camel_case( $key );
			} else {
				$new_key = $key;
			}

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
	private function camel_case( string $string ): string {

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

		// Ensure `class_name` config always exists, and serialize to a string.
		$class_name = implode( ' ', (array) ( $this->get_config( 'class_name' ) ?? [] ) );
		$this->set_config_value( 'class_name', $class_name );

		// Ensure `style` config always exists.
		$this->set_config_value( 'style', $this->get_config( 'style' ) ?? [] );

		// Add the theme name to the config as Irving core expects.
		$this->set_config_value( 'theme_name', self::camel_case( $this->get_theme() ) );
		$this->set_config_value( 'theme_options', array_keys( $this->camel_case_keys( array_flip( $this->get_theme_options() ) ) ) );

		// Filter out hidden config values.
		$config = $this->get_config();

		foreach ( $this->get_schema() as $key => $schema ) {
			if ( $schema['hidden'] && isset( $config[ $key ] ) ) {
				unset( $config[ $key ] );
			}
		}

		// Recursively apply to children.
		$children = array_map(
			function ( $child ) {
				if ( ! $child instanceof Component ) {
					return $child;
				}

				return $child->to_array();
			},
			$this->get_children()
		);

		return [
			'name'     => $this->get_name(),
			'_alias'   => $this->get_alias(),
			'config'   => (object) $this->camel_case_keys( $config ),
			'children' => $children,
		];
	}
}
