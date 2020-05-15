<?php
/**
 * Base component class.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Component.
 */
class Component implements \JsonSerializable {

	/**
	 * Name.
	 *
	 * Only supports a single `/` as a delimter between the namespace and
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
	 * @todo Implement this.
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
	protected $theme = 'default';

	/**
	 * Theme options.
	 *
	 * @var array
	 */
	protected $theme_options = [ 'default' ];

	/**
	 * Context provider.
	 *
	 * @todo Implement this.
	 *
	 * @var array
	 */
	protected $context_provider = [];

	/**
	 * Context consumer.
	 *
	 * @todo Implement this.
	 *
	 * @var array
	 */
	protected $context_consumer = [];

	/**
	 * Component constructor.
	 *
	 * @param null|string $name Component name.
	 * @param null|array  $args Possible constructor args.
	 */
	public function __construct( ?string $name = null, array $args = [] ) {

		// Set name.
		if ( ! is_null( $name ) ) {
			$this->set_name( $name );
		}

		// Validate our args.
		$args = wp_parse_args(
			$args,
			[
				'name'             => null,
				'config'           => null,
				'children'         => null,
				'theme'            => null,
				'theme_options'    => null,
				'context_provider' => null,
				'context_consumer' => null,
			]
		);

		// Set config.
		if ( ! is_null( $args['config'] ) ) {
			$this->set_config( $args['config'] );
		}

		// Set children.
		if ( ! is_null( $args['children'] ) ) {
			$this->set_children( $args['children'] );
		}

		// Set theme options.
		if ( ! is_null( $args['theme_options'] ) ) {
			$this->add_theme_options( $args['theme_options'] );
		}

		// Set theme.
		if ( ! is_null( $args['theme'] ) ) {
			$this->set_theme( $args['theme'] );
		}

		// Set context provider.
		if ( ! is_null( $args['context_provider'] ) ) {
			$this->set_context_provider( $args['context_provider'] );
		}

		// Set context consumer.
		if ( ! is_null( $args['context_consumer'] ) ) {
			$this->set_context_consumer( $args['context_consumer'] );
		}
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
		if ( is_array( $config_array_or_key ) ) {
			$this->config = $config_array_or_key;
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

		$this->set_children( $children );

		return $this;
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
	public function get_context_provider(): array {
		return $this->context_provider;
	}

	/**
	 * Set the context provider.
	 *
	 * @param array $context_provider Context provider.
	 * @return self
	 */
	public function set_context_provider( array $context_provider ): self {
		$this->context_provider = $context_provider;
		return $this;
	}

	/**
	 * Get the context consumer.
	 *
	 * @return array
	 */
	public function get_context_consumer(): array {
		return $this->context_consumer;
	}

	/**
	 * Set the context consumer.
	 *
	 * @param array $context_consumer Context consumer.
	 * @return self
	 */
	public function set_context_consumer( array $context_consumer ): self {
		$this->context_consumer = $context_consumer;
		return $this;
	}

	/**
	 * Run a user callback on this class. This can be used to create a fork in
	 * the method chain.
	 *
	 * @param callable $callable Callable.
	 * @param mixed    ...$args  Optional args to pass to the callback.
	 * @return function
	 */
	public function callback( callable $callable, ...$args ): Component {
		return call_user_func_array( $callable, array_merge( [ &$this ], $args ) );
	}

	/**
	 * Convert all array keys to camel case.
	 *
	 * @param array $array Array to convert.
	 * @return array Updated array with camel-cased keys.
	 */
	public function camel_case_keys( $array ) {

		// Setup for recursion.
		$camel_case_array = [];

		// Loop through each key.
		foreach ( $array as $key => $value ) {

			if ( is_array( $value ) ) {
				$value = $this->camel_case_keys( $value );
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

		// Replaace dashes and spaces with underscores.
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
	public function jsonSerialize(): array {
		return $this->to_array();
	}

	/**
	 * Convert the class to an array.
	 *
	 * @return array
	 */
	public function to_array(): array {

		// Camel case config keys.
		$this->set_config( $this->camel_case_keys( $this->get_config() ) );

		// Camel case theme options.
		$this->set_theme_options( $this->camel_case_keys( $this->get_theme_options() ) );
		$this->set_theme( self::camel_case( $this->get_theme() ) );

		// Add the theme name to the config as Irving core expects.
		$this->set_config( 'theme_name', $this->get_theme() );
		$this->set_config( 'theme_options', $this->get_theme_options() );

		return [
			'name'            => $this->get_name(),
			'config'          => (object) $this->get_config(),
			'children'        => $this->get_children(),
			'contextConsumer' => $this->get_context_provider(),
			'contextProvider' => $this->get_context_consumer(),
		];
	}
}
