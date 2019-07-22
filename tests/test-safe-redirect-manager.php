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
	}

	/**
	 * Test relative redirect.
	 */
	public function test_relative_to_relative() {
		srm_create_redirect( '/foo/', '/bar/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/' );
		$this->assertEquals( 'http://example.org/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from a relative URL to an absolute URL.
	 */
	public function test_relative_to_absolute() {
		// Internal, absolute URL destination.
		srm_create_redirect( '/foo/bar/', 'http://example.org/baz/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/' );
		$this->assertEquals( 'http://example.org/baz/', $response->data['redirectTo'] );

		// External, absolute URL destination.
		srm_create_redirect( '/foo/bar/baz/', 'http://another-example.org/baz/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/baz/' );
		$this->assertEquals( 'http://another-example.org/baz/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from an absolute, internal URL to a relative URL.
	 */
	public function test_absolute_to_relative() {
		srm_create_redirect( 'http://example.org/foo/', '/bar/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/' );
		$this->assertEquals( 'http://example.org/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from one absolute URL to another.
	 */
	public function test_absolute_to_absolute() {
		// Internal, absolute URL destination.
		srm_create_redirect( 'http://example.org/foo/bar/', 'http://example.org/baz/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/' );
		$this->assertEquals( 'http://example.org/baz/', $response->data['redirectTo'] );

		// External URL destination.
		srm_create_redirect( 'http://example.org/foo/bar/baz/', 'http://another-example.org/baz/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/baz/' );
		$this->assertEquals( 'http://another-example.org/baz/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirect_from URLs with and without a trailling slash will still match.
	 */
	public function test_trailing_slashes() {
		// Redirect from with trailing slash.
		srm_create_redirect( '/trailing-slash/', '/destination/' );

		// Request with trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash/' );
		$this->assertEquals( 'http://example.org/destination/', $response->data['redirectTo'] );

		// Request without trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash' );
		$this->assertEquals( 'http://example.org/destination/', $response->data['redirectTo'] );

		// Redirect from without trailing slash.
		srm_create_redirect( '/trailing-slash', '/destination/' );

		// Request with trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash/' );
		$this->assertEquals( 'http://example.org/destination/', $response->data['redirectTo'] );

		// Request without trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash' );
		$this->assertEquals( 'http://example.org/destination/', $response->data['redirectTo'] );
	}
}
