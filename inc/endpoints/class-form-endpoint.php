<?php
/**
 * Class file for form endpoints.
 *
 * @package WP_Irving
 */

namespace WP_Irving\REST_API;

/**
 * Form Endpoint.
 */
class Form_Endpoint extends Endpoint {

	/**
	 * Attach to required hooks for form endpoint
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
		 * Modify the output of the components route.
		 *
		 * @param array $form_endpoints {
		 *     Form endpoint slugs and callback functions.
		 *
		 *     @type string $slug The slug for the form endpoint.
		 *     @type string $callback response callback to use when the endpoint is called.
		 * }
		 */
		$form_endpoints = (array) apply_filters( 'wp_irving_form_endpoints', [] );

		if ( empty( $form_endpoints ) ) {
			return;
		}

		foreach ( $form_endpoints as $idx => $endpoint ) {
			register_rest_route(
				self::get_namespace(),
				'/form/' . $endpoint['slug'],
				[
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => $endpoint['callback'],
				]
			);
		}
	}

	/**
	 * Generic internal server error response.
	 *
	 * @return \WP_REST_Response
	 */
	public static function response_error() {
		$response = new \WP_REST_Response( [ 'ok' => false ] );
		$response->set_status( 500 );
		return $response;
	}

	/**
	 * Generic success response.
	 *
	 * @return \WP_REST_Response
	 */
	public static function response_success() {
		$response = new \WP_REST_Response( [ 'ok' => true ] );
		$response->set_status( 200 );
		return $response;
	}

	/**
	 * Invalid input response.
	 *
	 * @param array $validation Validation array in which keys are input names and values are messages.
	 * @return \WP_REST_Response
	 */
	public static function response_invalid( $validation ) {
		$response = new \WP_REST_Response( [ 'validation' => $validation ] );
		$response->set_status( 400 );
		return $response;
	}
}

new Form_Endpoint();
