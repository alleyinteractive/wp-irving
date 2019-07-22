<?php
/**
 * Class Legacy_Redirector_Tests
 *
 * @package WP_Irving
 */

/**
 * Tests for integration with WPCOM Legacy Redirector.
 */
class Legacy_Redirector_Tests extends WP_UnitTestCase {

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
		add_filter( 'wpcom_legacy_redirector_allow_insert', '__return_true', 10, 1 );
	}

	/**
	 * Test relative redirect.
	 */
	public function test_relative_to_relative() {
		WPCOM_Legacy_Redirector::insert_legacy_redirect( '/foo/', '/bar/', false );
		$response = self::$helpers->get_components_endpoint_response( '/foo/' );
		$this->assertEquals( 'http://example.org/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from a relative URL to an absolute URL
	 */
	public function test_relative_to_absolute() {
		// Internal, absolute URL destination.
		WPCOM_Legacy_Redirector::insert_legacy_redirect( '/foo/bar/', 'http://example.org/baz/', false );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/' );
		$this->assertEquals( 'http://example.org/baz/', $response->data['redirectTo'] );

		// External, absolute URL destination.
		WPCOM_Legacy_Redirector::insert_legacy_redirect( '/foo/bar/baz/', 'http://another-example.org/baz/', false );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/baz/' );
		$this->assertEquals( 'http://another-example.org/baz/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from an absolute, internal URL to a relative URL
	 */
	public function test_absolute_to_relative() {
		WPCOM_Legacy_Redirector::insert_legacy_redirect( 'http://example.org/foo/', '/bar/', false );
		$response = self::$helpers->get_components_endpoint_response( '/foo/' );
		$this->assertEquals( 'http://example.org/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from one absolute URL to another.
	 */
	public function test_absolute_to_absolute() {
		// Internal, absolute URL destination.
		WPCOM_Legacy_Redirector::insert_legacy_redirect( 'http://example.org/foo/bar/', 'http://example.org/baz/', false );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/' );
		$this->assertEquals( 'http://example.org/baz/', $response->data['redirectTo'] );

		// External URL destination.
		WPCOM_Legacy_Redirector::insert_legacy_redirect( 'http://example.org/foo/bar/baz/', 'http://another-example.org/baz/', false );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/baz/' );
		$this->assertEquals( 'http://another-example.org/baz/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirect_from URLs with and without a trailling slash will still match.
	 */
	public function test_trailing_slashes() {
		// Redirect from with trailing slash.
		WPCOM_Legacy_Redirector::insert_legacy_redirect( '/trailing-slash/', '/destination/', false );

		// With trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash/' );
		$this->assertEquals( 'http://example.org/destination/', $response->data['redirectTo'] );

		// Without trailing slash
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash' );
		$this->assertEquals( 'http://example.org/destination/', $response->data['redirectTo'] );

		// Redirect from without trailing slash.
		WPCOM_Legacy_Redirector::insert_legacy_redirect( '/trailing-slash', '/destination/' );

		// Request with trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash/' );
		$this->assertEquals( 'http://example.org/destination/', $response->data['redirectTo'] );

		// Request without trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash' );
		$this->assertEquals( 'http://example.org/destination/', $response->data['redirectTo'] );
	}
}
