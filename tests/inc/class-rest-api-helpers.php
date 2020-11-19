<?php
/**
 * Class REST_API_Helpers
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\REST_API;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * Helpers to run an Irving REST API request.
 */
class Test_REST_API_Helpers {

	/**
	 * Helper to get a components endpoint response.
	 *
	 * @param array $args Component query args.
	 * @return WP_REST_Response
	 */
	public static function get_components_response( $args = [] ): WP_REST_Response {

		$request = new WP_REST_Request(
			'GET',
			add_query_arg(
				$args,
				'http://' . WP_TESTS_DOMAIN . '/wp-json/irving/v1/components/'
			)
		);

		return rest_do_request( $request );
	}
}
