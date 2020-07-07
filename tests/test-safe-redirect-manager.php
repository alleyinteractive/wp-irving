<?php
/**
 * Class Safe_Redirect_Manager_Tests
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_UnitTestCase;

/**
 * Tests for integration with Safe Redirect Manager.
 *
 * @group redirects
 */
class Safe_Redirect_Manager_Tests extends WP_UnitTestCase {

	/**
	 * Helpers class instance.
	 *
	 * @var \WP_Irving_Test_Helpers
	 */
	public static $helpers;

	/**
	 * Components endpoint instance.
	 *
	 * @var \REST_API\Components_Endpoint
	 */
	public static $components_endpoint;

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
		self::$helpers = new Test_Helpers();
	}

	/**
	 * Set up test data.
	 */
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
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from a relative URL to an absolute URL.
	 */
	public function test_relative_to_absolute() {
		$this->markasIncomplete();

		// Internal, absolute URL destination.
		srm_create_redirect( '/foo/bar/', 'http://' . WP_TESTS_DOMAIN . '/baz/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/baz/', $response->data['redirectTo'] );

		// External, absolute URL destination.
		srm_create_redirect( '/foo/bar/baz/', 'http://another-' . WP_TESTS_DOMAIN . '/baz/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/baz/' );
		$this->assertEquals( 'http://another-' . WP_TESTS_DOMAIN . '/baz/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from an absolute, internal URL to a relative URL.
	 */
	public function test_absolute_to_relative() {
		$this->markasIncomplete();

		srm_create_redirect( 'http://' . WP_TESTS_DOMAIN . '/foo/', '/bar/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from one absolute URL to another.
	 */
	public function test_absolute_to_absolute() {
		$this->markasIncomplete();

		// Internal, absolute URL destination.
		srm_create_redirect( 'http://' . WP_TESTS_DOMAIN . '/foo/bar/', 'http://' . WP_TESTS_DOMAIN . '/baz/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/baz/', $response->data['redirectTo'] );

		// External URL destination.
		srm_create_redirect( 'http://' . WP_TESTS_DOMAIN . '/foo/bar/baz/', 'http://another-' . WP_TESTS_DOMAIN . '/baz/' );
		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/baz/' );
		$this->assertEquals( 'http://another-' . WP_TESTS_DOMAIN . '/baz/', $response->data['redirectTo'] );
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
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/destination/', $response->data['redirectTo'] );

		// Request without trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/destination/', $response->data['redirectTo'] );

		// Redirect from without trailing slash.
		srm_create_redirect( '/trailing-slash', '/destination/' );

		// Request with trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/destination/', $response->data['redirectTo'] );

		// Request without trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/destination/', $response->data['redirectTo'] );
	}

	/**
	 * Helper to conditionally mark a test as incomplete.
	 */
	public function markasIncomplete() {
		if ( ! method_exists( $this->object, 'match_redirect' ) ) {
			$this->markTestIncomplete(
				'match_redirect not part of Safe Redirect Manager.'
			);
		}
	}
}
