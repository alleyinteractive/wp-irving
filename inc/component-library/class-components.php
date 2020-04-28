<?php
/**
 * Basee.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Component.
 */
class Component implements \JsonSerializable {

	/**
	 * Unique component name.
	 *
	 * @var string
	 */
	public $name = 'wp-irving/component';

	/**
	 * Component attributes.
	 *
	 * @var array
	 */
	public $attributes = [];

	/**
	 * Component children.
	 *
	 * @var array
	 */
	public $children = [];

	/**
	 * Component constructor.
	 */
	public function __construct() {
	}

	/**
	 * Set the component name.
	 *
	 * @param  string $name New component name.
	 * @return self
	 */
	public function set_name( string $name ) : self {
		$this->name = $name;
		return $this;
	}

	public function set_attributes( array $attributes ) {
		$this->set_attributes_without_firing_callback( $attributes );

		foreach ( $attributes as $attribute_)

		array_map(
			function( $key) {
				$this->attribute_has_set( $key, $value );
			},
			$attributes
		);
	}

	public function set_attributes_without_firing_callback( array $attributes ):self {
		$this->attributes = $attributes;
		return $this;
	}

	public function merge_attributes() {

	}

	public function merge_attributes_without_firing_callback() {

	}

	public function set_attribute( string $attribute_key, $attribute_value ):self {
		return $this;
	}

	public function set_attribute_without_firing_callback( $key, $value ):self {
		return $this;
	}

	public function do_attribute_callback( string $key ):self {
		$method_name = "attribute_has_set"
	}

	public function attribute_has_set( $key, $value ) {

		if ( method_exists( $this, $callback_method ) && $do_callback ) {
			$this->$callback_method();
		}

		return $this;
	}

	public function get_attributes() {
		return $this->attributes;
	}

	public function get_attribute( string $key ) {
		return $this->attributes[ $key ] ?? null;
	}

	public function get_children() {}
	public function set_children() {}
	public function set_child() {}
	public function append_child() {}
	public function prepend_child() {}


	public function is_valid() {}
	public function is_invalid() {}

	public function set_is_valid() {}
	public function set_is_invalid() {}

	public function callback() {}

	public function set_theme() {}

	public function


	/**
	 * Run a user callback on this class. This can be used to create a fork in
	 * the method chain.
	 *
	 * @param callable $callable Callable.
	 * @return function
	 */
	public function callback( $callable ) {
		return call_user_func_array( $callable, [ &$this ] );
	}

	/**
	 * Execute a function on each child of this component.
	 *
	 * @param callable $callback Callback function.
	 * @return self
	 */
	public function children_callback( $callback ) : self {
		$this->children = array_map( $callback, $this->children );
		return $this;
	}

	/**
	 * Helper to set this component's is_valid flag to false.
	 *
	 * @return self
	 */
	public function set_invalid() : self {
		$this->is_valid = false;
		return $this;
	}

	/**
	 * Helper to set this component's is_valid flag to true.
	 *
	 * @return self
	 */
	public function set_valid() : self {
		$this->is_valid = true;
		return $this;
	}

	/**
	 * Helper to set this component's is_valid flag to false.
	 *
	 * @return self
	 */
	public function is_invalid() : self {
		return $this->set_invalid();
	}

	/**
	 * Helper to set this component's is_valid flag to true.
	 *
	 * @return self
	 */
	public function is_valid() : self {
		return $this->set_valid();
	}

	public function validate_component() {

	}

	/**
	 * Trigger a fatal error on this component and log a message.
	 *
	 * @param string $error_message Optional error message.
	 * @return self
	 */
	public function has_error( $error_message = '' ) : self {

		// If a message exists and WP debugging is enabled for logging.
		if (
			! empty( $error_message )
			&& defined( 'WP_DEBUG' )
			&& WP_DEBUG
			&& defined( 'WP_DEBUG_LOG' )
			&& WP_DEBUG_LOG
		) {
			error_log( $error_message );
		}
		return $this->set_invalid();
	}

	/**
	 * Helper to set theme on this component
	 *
	 * @param string $theme_name Name of theme to set.
	 * @return self
	 */
	public function set_theme( $theme_name ) : self {
		$this->set_config( 'theme_name', $theme_name );
		return $this;
	}

	/**
	 * Helper to recursively set themes on child components.
	 *
	 * @param array $theme_mapping Array in which keys are component $name properties and values are the theme to use for that component.
	 * @return self
	 */
	public function set_child_themes( $theme_mapping ) : self {
		$component_names = array_keys( $theme_mapping );

		// Recursively set themes for children.
		if ( ! empty( $this->children ) ) {
			foreach ( $this->children as $child ) {
				if ( ! empty( $theme_mapping[ $child->name ] ) ) {
					$child->set_config( 'theme_name', $theme_mapping[ $child->name ] );
				}

				$child->set_child_themes( $theme_mapping );
			}
		}

		return $this;
	}

	/**
	 * Helper to output this class as an array.
	 *
	 * @return array
	 */
	public function to_array() : array {

		// For invalid components, append `-invalid` to the name to indicate
		// that there was a fatal error and it should not be rendered. This
		// approach allows us to still see the data in the endpoint for
		// debugging purposes (and even create a fallback component if
		// desired).
		if ( ! $this->is_valid ) {
			$this->name = $this->name . '-invalid';
		}

		return [
			'name'     => $this->name,
			'config'   => (object) $this->camel_case_keys( $this->config ),
			'children' => array_filter( $this->children ),
		];
	}

	/**
	 * Convert all array keys to camel case.
	 *
	 * @param array $array        Array to convert.
	 * @return array Updated array with camel-cased keys.
	 */
	public function camel_case_keys( $array ) : array {
		// Setup for recursion.
		$camel_case_array = [];

		// Loop through each key.
		foreach ( $array as $key => $value ) {
			// Only return keys that are white-listed. Leave $whitelist empty
			// to disable.
			if (
				! empty( $this->whitelist )
				&& ! in_array( $key, $this->whitelist, true )
			) {
				unset( $array[ $key ] );
				continue;
			}

			// Explode each part by underscore.
			$words = explode( '_', $key );

			// Capitalize each key part.
			array_walk(
				$words,
				function( &$word ) {
					$word = ucwords( $word );
				}
			);

			// Reassemble key.
			$new_key = implode( '', $words );

			// Lowercase the first character.
			$new_key[0] = strtolower( $new_key[0] );

			if (
				! is_array( $value )
				// Don't recursively camelCase if this key is in the $preserve_inner_keys property.
				|| ( ! empty( $this->preserve_inner_keys ) && in_array( $key, $this->preserve_inner_keys, true ) )
			) {
				// Set new key value.
				$camel_case_array[ $new_key ] = $value;
			} else {
				// Set new key value, but process the nested array.
				$camel_case_array[ $new_key ] = $this->camel_case_keys( $array[ $key ] );
			}
		}

		return $camel_case_array;
	}

	/**
	 * Use custom to_array method when component is serialized for API response.
	 *
	 * @return array
	 */
	public function jsonSerialize() : array {
		return $this->to_array();
	}
}
