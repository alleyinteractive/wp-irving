<?php
/**
 * Class file for endpoint.
 *
 * @package WP_Irving
 */

namespace WP_Irving\REST_API;

/**
 * Endpoint.
 */
class Endpoint {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Get namespace for endpoints.
	 *
	 * @return string
	 */
	public static function get_namespace() {
		return self::wp_irving_rest_namespace() . '/' . self::wp_irving_rest_version();
	}

	/**
	 * WP Irving REST API namespace.
	 *
	 * @return string
	 */
	public static function wp_irving_rest_namespace() {

		/**
		 * Filter API namespace.
		 *
		 * @param string $namespace Irving namespace.
		 */
		return apply_filters( 'wp_irving_rest_namespace', 'irving' );
	}

	/**
	 * WP Irving REST API version.
	 *
	 * @return string
	 */
	public static function wp_irving_rest_version() {

		/**
		 * Filter API version.
		 *
		 * @since 0.1.0
		 *
		 * @param string $version WP Irving version.
		 */
		return apply_filters( 'wp_irving_rest_version', 'v1' );
	}
}
