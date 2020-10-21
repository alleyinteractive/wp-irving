<?php
/**
 * Class Test_Class_Components_Endpoint.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\REST_API;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * Test the components endpoint functionality.
 *
 * @group endpoints
 */
class Test_Class_Components_Endpoint extends WP_UnitTestCase {

	/**
	 * Create an instance of WP_REST_Request with an empty path.
	 *
	 * @param string $context Request context.
	 * @param string $path    Requested path.
	 * @return WP_REST_REQUEST
	 */
	public function create_rest_request( $context = 'page', $path = null ): WP_REST_REQUEST {
		$params = [
			'context' => '?context=' . $context,
			'path'    => $path ? '&path=' . $path : '',
		];

		// Build full url and do a POST request.
		$request = new WP_REST_Request( 'GET', 'irving/v1/components' . $params['context'] . $params['path'] );

		return $request;
	}

	/**
	 * Get the response from a hit on the components endpoint for the homepage.
	 *
	 * @return WP_REST_Response
	 */
	public function get_homepage_endpoint_response(): WP_REST_RESPONSE {
		return ( new REST_API\Components_Endpoint() )
			->get_route_response(
				$this->create_rest_request( 'page', '/' )
			);
	}

	/**
	 * Get the response from a hit on the components endpoint with an empty path.
	 *
	 * @return WP_REST_Response
	 */
	public function get_empty_path_endpoint_response(): WP_REST_RESPONSE {
		return ( new REST_API\Components_Endpoint() )
			->get_route_response(
				$this->create_rest_request( 'page' )
			);
	}

	/**
	 * Test the empty path endpoint response.
	 */
	public function test_empty_path_response() {
		$this->assertEquals(
			$this->get_homepage_endpoint_response(),
			$this->get_empty_path_endpoint_response(),
			'Requests with an empty path match the homepage endpoint response.'
		);
	}
}
