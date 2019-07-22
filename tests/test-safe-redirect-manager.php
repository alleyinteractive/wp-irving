<?php
/**
 * Class Safe_Redirect_Manager_Tests
 *
 * @package WP_Irving
 */

/**
 * Tests for integration with Safe Redirect Maanger.
 */
class Safe_Redirect_Manager_Tests extends WP_UnitTestCase {

	/**
	 * Helpers class instance.
	 *
	 * \WP_Irving_Test_Helpers
	 */
	static $helpers;

	/**
	 * Components endpoint instance.
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
	 * Test relative redirect.
	 */
	public function test_relative_to_relative() {
		srm_create_redirect( '/foo/', '/bar/' );

		$request = self::$helpers->create_rest_request( '/foo/' );
		$response = self::$components_endpoint->get_route_response( $request );

		$this->assertEquals( 'http://example.org/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from a relative URL to an absolute URL.
	 */
	public function test_relative_to_absolute() {
		// Internal, absolute URL destination.
		srm_create_redirect( '/foo/bar/', 'http://example.org/baz/' );

		$request = self::$helpers->create_rest_request( '/foo/bar/' );
		$response = self::$components_endpoint->get_route_response( $request );
		$this->assertEquals( 'http://example.org/baz/', $response->data['redirectTo'] );

		// External, absolute URL destination.
		srm_create_redirect( '/foo/bar/baz/', 'http://another-example.org/baz/' );

		$request = self::$helpers->create_rest_request( '/foo/bar/baz/' );
		$response = self::$components_endpoint->get_route_response( $request );
		$this->assertEquals( 'http://another-example.org/baz/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from an absolute, internal URL to a relative URL.
	 */
	public function test_absolute_to_relative() {
		srm_create_redirect( 'http://example.org/foo/', '/bar/' );

		$request = self::$helpers->create_rest_request( '/foo/' );
		$response = self::$components_endpoint->get_route_response( $request );
		$this->assertEquals( 'http://example.org/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from one absolute URL to another.
	 */
	public function test_absolute_to_absolute() {
		// Internal, absolute URL destination.
		srm_create_redirect( 'http://example.org/foo/bar/', 'http://example.org/baz/' );

		$request = self::$helpers->create_rest_request( '/foo/bar/' );
		$internal_response = self::$components_endpoint->get_route_response( $request );
		$this->assertEquals( 'http://example.org/baz/', $internal_response->data['redirectTo'] );

		// External URL destination.
		srm_create_redirect( 'http://example.org/foo/bar/baz/', 'http://another-example.org/baz/' );

		$request = self::$helpers->create_rest_request( '/foo/bar/baz/' );
		$external_response = self::$components_endpoint->get_route_response( $request );
		$this->assertEquals( 'http://another-example.org/baz/', $external_response->data['redirectTo'] );
	}

	/**
	 * Test redirect_from URLs with and without a trailling slash will still match.
	 */
	public function test_trailing_slashes() {
		srm_create_redirect( '/trailing-slash/', '/destination/' );

		// With trailing slash.
		$request = self::$helpers->create_rest_request( '/trailing-slash/' );
		$response = self::$components_endpoint->get_route_response( $request );
		$this->assertEquals( 'http://example.org/destination/', $response->data['redirectTo'] );

		// Without trailing slash.
		$request = self::$helpers->create_rest_request( '/trailing-slash' );
		$response = self::$components_endpoint->get_route_response( $request );
		$this->assertEquals( 'http://example.org/destination/', $response->data['redirectTo'] );
	}
}
