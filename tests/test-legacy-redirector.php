<?php
/**
 * Class Legacy_Redirector_Tests
 *
 * @package WP_Irving
 * @todo should the `$validate` param of `insert_legacy_redirect` be set to `true`?
 */

namespace WP_Irving;

use WP_UnitTestCase;
use WPCOM_Legacy_Redirector;
/**
 * Tests for integration with WPCOM Legacy Redirector.
 *
 * @group redirects
 */
class Legacy_Redirector_Tests extends WP_UnitTestCase {

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
	 * Test suite setup.
	 */
	public static function setUpBeforeClass() {
		self::$helpers = new Test_Helpers();
	}

	/**
	 * Skip tests if necessary.
	 */
	public function setUp() {
		parent::setUp();

		$this->markTestSkipped( 'Revisit after refactor.' );

		if ( ! defined( 'WPCOM_LEGACY_REDIRECTOR_VERSION' ) ) {
			$this->markTestSkipped( 'WPCOM Legacy Redirector is not installed.' );
		}

		// Ensure that we have permission to insert redirects.
		add_filter( 'wpcom_legacy_redirector_allow_insert', '__return_true', 10, 1 );
	}

	/**
	 * Test relative redirect.
	 */
	public function test_relative_to_relative() {
		$created_redirect = WPCOM_Legacy_Redirector::insert_legacy_redirect( '/foo/', '/bar/', false );
		$this->assertEquals( true, $created_redirect, 'Could not insert redirect for /foo/bar/' );

		$response = self::$helpers->get_components_endpoint_response( '/foo/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from a relative URL to an absolute URL
	 */
	public function test_relative_to_absolute() {
		// Internal, absolute URL destination.
		$created_redirect = WPCOM_Legacy_Redirector::insert_legacy_redirect( '/foo/bar/', 'http://' . WP_TESTS_DOMAIN . '/baz/', false );
		$this->assertEquals( true, $created_redirect, 'Could not insert redirect for /foo/bar/' );

		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/baz/', $response->data['redirectTo'] );

		// External, absolute URL destination.
		$created_redirect = WPCOM_Legacy_Redirector::insert_legacy_redirect( '/foo/bar/baz/', 'http://another-example.org/baz/', false );
		$this->assertEquals( true, $created_redirect, 'Could not insert redirect for /foo/bar/' );

		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/baz/' );
		$this->assertEquals( 'http://another-example.org/baz/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from an absolute, internal URL to a relative URL
	 */
	public function test_absolute_to_relative() {
		$created_redirect = WPCOM_Legacy_Redirector::insert_legacy_redirect( 'http://' . WP_TESTS_DOMAIN . '/foo/', '/bar/', false );
		$this->assertEquals( true, $created_redirect, 'Could not insert redirect for /foo/bar/' );

		$response = self::$helpers->get_components_endpoint_response( '/foo/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/bar/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirects from one absolute URL to another.
	 */
	public function test_absolute_to_absolute() {
		// Internal, absolute URL destination.
		$created_redirect = WPCOM_Legacy_Redirector::insert_legacy_redirect( 'http://' . WP_TESTS_DOMAIN . '/foo/bar/', 'http://' . WP_TESTS_DOMAIN . '/baz/', false );
		$this->assertEquals( true, $created_redirect, 'Could not insert redirect for /foo/bar/' );

		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/baz/', $response->data['redirectTo'] );

		// External URL destination.
		$created_redirect = WPCOM_Legacy_Redirector::insert_legacy_redirect( 'http://' . WP_TESTS_DOMAIN . '/foo/bar/baz/', 'http://another-example.org/baz/', false );
		$this->assertEquals( true, $created_redirect, 'Could not insert redirect for /foo/bar/' );

		$response = self::$helpers->get_components_endpoint_response( '/foo/bar/baz/' );
		$this->assertEquals( 'http://another-example.org/baz/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirect_from URLs with a trailing slash.
	 */
	public function test_with_trailing_slashes() {
		// Redirect from with trailing slash.
		$created_redirect = WPCOM_Legacy_Redirector::insert_legacy_redirect( '/trailing-slash/', '/destination/', false );
		$this->assertEquals( true, $created_redirect, 'Could not insert redirect for /foo/bar/' );

		// With trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/destination/', $response->data['redirectTo'] );

		// Without trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/destination/', $response->data['redirectTo'] );
	}

	/**
	 * Test redirect_from URLs without a trailing slash.
	 */
	public function test_without_trailing_slashes() {
		// Redirect from without trailing slash.
		WPCOM_Legacy_Redirector::insert_legacy_redirect( '/trailing-slash', '/destination/', false );

		// Request with trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/destination/', $response->data['redirectTo'] );

		// Request without trailing slash.
		$response = self::$helpers->get_components_endpoint_response( '/trailing-slash' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/destination/', $response->data['redirectTo'] );
	}
}
