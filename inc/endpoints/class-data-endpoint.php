<?php
/**
 * Class file for data endpoints.
 *
 * @package WP_Irving
 */

namespace WP_Irving\REST_API;

/**
 * Data Endpoint.
 */
class Data_Endpoint extends Endpoint {

	/**
	 * Attach to required hooks for data endpoint.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register the REST API routes.
	 */
	public function register_rest_routes() {
		/**
		 * Modify the output of the data route.
		 *
		 * @param array $data_endpoints {
		 *     Data endpoint slugs and callback functions.
		 *
		 *     @type string   $slug                The slug for the data endpoint.
		 *     @type callable $callback            Response callback to use when the endpoint is called.
		 *     @type callable $permission_callback Permission callback to use when the endpoint is called.
		 * }
		 */
		$data_endpoints = (array) apply_filters( 'wp_irving_data_endpoints', [] );

		if ( empty( $data_endpoints ) ) {
			return;
		}

		// Register each endpoint.
		foreach ( $data_endpoints as $args ) {

			$args = wp_parse_args(
				$args,
				[
					'callback'            => '__return_true',
					'methods'             => \WP_REST_Server::READABLE,
					'permission_callback' => '__return_true',
					'slug'                => '',
				]
			);

			// Ensure we have a slug.
			if ( empty( $args['slug'] ) || ! is_string( $args['slug'] ) ) {
				break;
			}

			// Build the route, and unset the slug.
			$route = '/data/' . $args['slug'];
			unset( $args['slug'] );

			register_rest_route( self::get_namespace(), $route, $args );
		}
	}
}
