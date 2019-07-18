<?php
/**
 * Class SRM_Redirects_Test
 *
 * @package WP_Irving
 */

/**
 * Tests for Safe Redirect Maanger redirects
 */
class SRM_Redirects_Test extends WP_UnitTestCase {

	/**
	 * Helpers class instance
	 *
	 * \WP_Irving_Test_Helpers
	 */
	static $helpers;

	/**
	 * Components endpoint instance
	 *
	 * \WP_Irving\REST_API\Components_Endpoint
	 */
	static $components_endpoint;

	/**
	 * Test suite setup.
	 */
	public static function setUpBeforeClass() {
		self::$helpers = new \WP_Irving\Test_Helpers();
		self::$components_endpoint = new \WP_Irving\REST_API\Components_Endpoint();
	}

	/**
	 * Test relative redirects
	 */
	public function test_relative_redirects() {
		srm_create_redirect( '/foo/', '/bar/' );

		$request = self::$helpers->create_rest_request( '/foo/' );
		$response = self::$components_endpoint->get_route_response( $request );

		print_r( $response );
	}
}
