<?php
/**
 * Class Test_Redirects
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_UnitTestCase;

/**
 * Test redirect functionality.
 *
 * @group redirects
 */
class Test_Redirects extends WP_UnitTestCase {

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
	 * Test post.
	 *
	 * @var int Post ID.
	 */
	public static $post_id;

	/**
	 * Test suite setup.
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$helpers = new Test_REST_API_Helpers();
	}

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetupBeforeClass( $factory ) {
		self::$post_id = $factory->post->create(
			[
				'post_title' => 'Hello World',
				'post_name'  => 'hello-world',
			]
		);
	}

	/**
	 * Test the ability to automatically redirect from old post slugs.
	 */
	public function test_handle_wp_old_slug_redirects() {

		// When this response looks correct, the tests should pass too.
		$response = self::$helpers->get_components_response( [ 'path' => '/hello-world/' ] );
		print_r( $response );
		die();

		// Confirm that `/hello-world/` doesn't redirect.
		$response = self::$helpers->get_components_endpoint_response( '/hello-world/' );
		$this->assertEquals( '', $response->data['redirectTo'], 'redirectTo was not empty.' );
		$this->assertEquals( 0, $response->data['redirectStatus'], 'redirectStatus was not empty.' );

		// Changing the post name should trigger a redirect.
		wp_update_post(
			[
				'ID'         => self::$post_id,
				'post_title' => 'Foo Bar',
				'post_name'  => 'foo-bar',
			]
		);

		// Confirm that `/hello-world/` redirects to `/foo-bar/`.
		$response = self::$helpers->get_components_endpoint_response( '/hello-world/' );
		$this->assertEquals( '/foo-bar/', $response->data['redirectTo'], 'redirectTo was not empty.' );
		$this->assertEquals( 301, $response->data['redirectStatus'], 'redirectStatus was not empty.' );

		// Change it again to check for a new redirect.
		wp_update_post(
			[
				'ID'         => self::$post_id,
				'post_title' => 'Irving',
				'post_name'  => 'irving',
			]
		);

		// // Confirm that `/hello-world/` redirects to `/irving/`.
		$response = self::$helpers->get_components_endpoint_response( '/hello-world/' );
		$this->assertEquals( '/irving/', $response->data['redirectTo'], 'redirectTo was not empty.' );
		$this->assertEquals( 301, $response->data['redirectStatus'], 'redirectStatus was not empty.' );

		// // Confirm that `/foo-bar/` redirects to `/irving/`.
		$response = self::$helpers->get_components_endpoint_response( '/foo-bar/' );
		$this->assertEquals( '/irving/', $response->data['redirectTo'], 'redirectTo was not empty.' );
		$this->assertEquals( 301, $response->data['redirectStatus'], 'redirectStatus was not empty.' );
	}
}
