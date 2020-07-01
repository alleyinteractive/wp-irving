<?php
/**
 * Class Test_Helpers
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Tests for Safe Redirect Manager redirects
 */
class Test_Helpers {

	/**
	 * Create an instance of WP_REST_Request class with a given path.
	 *
	 * @param string $path Request path.
	 * @return WP_REST_Request
	 */
	public function create_rest_request( $path ) {
		$base_url = 'http://' . WP_TESTS_DOMAIN . '/wp-json/irving/v1/components/';
		$params   = [
			'path' => $path,
		];
		$full_url = add_query_arg( $base_url, $params );
		$request  = new WP_REST_Request( 'GET', $base_url );
		$request->set_query_params( $params );
		$_SERVER['REQUEST_URI'] = $full_url;

		return $request;
	}

	/**
	 * Create a components endpoint response given a path.
	 *
	 * @param string $path Request path.
	 * @return WP_REST_Response
	 */
	public function get_components_endpoint_response( $path ) {
		$request  = $this->create_rest_request( $path );
		$response = ( new REST_API\Components_Endpoint() )
			->get_route_response( $request );

		return $response;
	}
}
