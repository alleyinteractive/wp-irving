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
	 * Holding the SRM object.
	 *
	 * @var SRM_Redirect
	 */
	public $object;

	/**
	 * Test suite setup.
	 */
	public static function setUpBeforeClass() {
		self::$helpers = new \WP_Irving\Test_Helpers();
	}

	public function setUp() {
		parent::setUp();

		if ( ! class_exists( 'SRM_Redirect' ) ) {
			$this->markTestSkipped( 'SRM_Redirect is not available.' );
			return;
		}

		$this->object = \SRM_Redirect::factory();
	}

	/**
	 * Test relative redirect.
	 */
	public function test_relative_to_relative() {
		$this->markasIncomplete();

		srm_create_redirect( '/foo/', '/bar/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/' );
		$this->assertEquals( 'http://example.org/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from a relative URL to an absolute URL.
	 */
	public function test_relative_to_absolute() {
		$this->markasIncomplete();

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
		$this->markasIncomplete();

		srm_create_redirect( 'http://example.org/foo/', '/bar/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/' );
		$this->assertEquals( 'http://example.org/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from one absolute URL to another.
	 */
	public function test_absolute_to_absolute() {
		$this->markasIncomplete();

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
		$this->markasIncomplete();

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

	public function markasIncomplete() {
		if ( ! method_exists( $this->object, 'match_redirect' ) ) {
			$this->markTestIncomplete(
				'match_redirect not part of Safe Redirect Manager.'
			);
		}
	}
}
