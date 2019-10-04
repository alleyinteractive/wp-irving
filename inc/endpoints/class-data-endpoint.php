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
		 *     @type string   $slug     The slug for the data endpoint.
		 *     @type callable $callback Response callback to use when the endpoint is called.
		 * }
		 */
		$data_endpoints = (array) apply_filters( 'wp_irving_data_endpoints', [] );

		if ( empty( $data_endpoints ) ) {
			return;
		}

		foreach ( $data_endpoints as $endpoint ) {
			register_rest_route(
				self::get_namespace(),
				'/data/' . $endpoint['slug'],
				[
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => $endpoint['callback'],
				]
			);
		}
	}
}

new Data_Endpoint();
