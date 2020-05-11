<?php
/**
 * Context store for use in template hydration.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

class Context_Store {

	/**
	 * List of context objects.
	 *
	 * @var array
	 */
	protected $context = [];

	/**
	 * Get the value of a context key.
	 *
	 * @param string $key The context key to retrieve.
	 * @return mixed The value being returned
	 */
	public function get( string $key ) {
		return reset( $this->context )[$key];
	}

	/**
	 * Return the context to it's previous state.
	 *
	 * @return true
	 */
	public function reset() {
		array_shift( $this->context );

		return true;
	}

	/**
	 * Set a context value for a specific key.
	 *
	 * @param string $context The key to set.
	 * @param mixed $value The value to set.
	 * @return bool
	 */
	public function set( string $context, $value ) {
 		$new_context = [ $context => $value ];

		array_unshift( $this->context, array_merge( $this->context, $new_context ) );

		return true;
	}
}
