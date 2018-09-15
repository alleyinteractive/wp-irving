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
class Component implements \JsonSerializable {

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
	 * Determine which config keys should be passed into result.
	 *
	 * @var array
	 */
	public $whitelist = [];

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
		$this->name     = ! empty( $this->name ) ? $this->name : $name;
		$this->config   = ! empty( $this->config ) ? $this->config : $config;
		$this->children = ! empty( $this->children ) ? $this->children : $children;

		// Conform config.
		$this->config = wp_parse_args( $this->config, $this->default_config() );
	}

	/**
	 * Define a default config shape.
	 *
	 * @return array Default config.
	 */
	public function default_config() {
		return [];
	}

	/**
	 * Helper to set a top level config value.
	 *
	 * @param  string $key   Config key.
	 * @param  mixed  $value Config value.
	 * @return mixed An instance of this class.
	 */
	public function set_config( $key, $value ) {
		$this->config[ $key ] = $value;
		return $this;
	}

	/**
	 * Helper to set a top level config value.
	 *
	 * @param  string $key   Config key.
	 * @return mixed An instance of this class.
	 */
	public function get_config( $key ) {
		if ( array_key_exists( $key, $this->config ) ) {
			return $this->config[ $key ];
		}
		return null;
	}

	/**
	 * Helper to set children components.
	 *
	 * @param  array   $children Children for this component.
	 * @param  boolean $append   Append children to existing children.
	 * @return mixed An instance of this class.
	 */
	public function set_children( array $children, $append = false ) {
		if ( $append ) {
			$this->children = array_merge(
				$this->children,
				array_filter( $children )
			);
		} else {
			$this->children = array_filter( $children );
		}
		return $this;
	}

	/**
	 * Helper to change a components name.
	 *
	 * @param  string $name New component name.
	 * @return mixed An instance of this class.
	 */
	public function set_name( string $name ) {
		$this->name = $name;

		return $this;
	}

	/**
	 * Helper to change the name of all children components.
	 *
	 * @param  string $name New component name.
	 * @return mixed An instance of this class.
	 */
	public function set_name_of_children( string $name ) {

		// Map through all children.
		$this->children = array_map( function( $child ) use ( $name ) {

			// Check if `set_name()` exists and call it.
			if ( method_exists( $child, 'set_name' ) ) {
				$child->set_name( $name );
			}

			return $child;
		}, $this->children );

		return $this;
	}

	/**
	 * Helper to output this class as an array.
	 *
	 * @return array
	 */
	public function to_array() : array {
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
	 * @param array $array_holder Parent array holder for recursive array.
	 * @return array Updated array with camel-cased keys.
	 */
	public function camel_case_keys( $array, $array_holder = [] ) {

		// Setup for recursion.
		$camel_case_array = ! empty( $array_holder ) ? $array_holder : [];

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
			array_walk( $words, function( &$word ) {
				$word = ucwords( $word );
			} );

			// Reassemble key.
			$new_key = implode( '', $words );

			// Lowercase the first character.
			$new_key[0] = strtolower( $new_key[0] );

			if ( ! is_array( $value ) ) {
				// Set new key value.
				$camel_case_array[ $new_key ] = $value;
			} else {
				// Set new key value, but process the nested array.
				$camel_case_array[ $new_key ] = $this->camel_case_keys( $value, $camel_case_array[ $new_key ] );
			}
		}

		return $camel_case_array;
	}

	/**
	 * Use custom to_array method when component is serialized for API response.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}

	/**
	 * Helper function to add an Image component child.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size          Image size.
	 */
	public function add_child_image( int $attachment_id, string $size = 'full' ) {

		// Create an Image component.
		$image_component = ( new \WP_Irving\Component\Image() )
			->set_attachment_id( absint( $attachment_id ) )
			->set_config_for_size( $size );

		// Validate and append to children.
		if ( 0 !== absint( $image_component->get_config( 'attachment_id' ) ) ) {
			$this->children[] = $image_component;
		}
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
