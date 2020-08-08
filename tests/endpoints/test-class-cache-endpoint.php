<?php
/**
 * Class Test_Class_Cache_Endpoint.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\REST_API;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * Test the cache endpoint functionality.
 *
 * @group endpoints
 */
class Test_Class_Cache_Endpoint extends WP_UnitTestCase {

	/**
	 * Create an instance of WP_REST_Request class with a given path.
	 *
	 * @param array $params Endpoint parameters.
	 * @return WP_REST_Request
	 */
	public function create_rest_request( $params = [] ): WP_REST_REQUEST {

		// Build the cache endpoint url.
		$endpoint_url = 'irving/v1/purge-cache/';

		// Endpoint parameters.
		$params = wp_parse_args(
			$params,
			[
				'action' => 'irving_page_cache_purge',
				'route'  => '/',
			]
		);

		// Build full url and do a POST request.
		$request = new WP_REST_Request( 'POST', $endpoint_url );
		$request->set_body_params( $params );

		return $request;
	}

	/**
	 * Get the response from a hit on the cache endpoint.
	 *
	 * @param array $params Endpoint parameters.
	 * @return WP_REST_Response
	 */
	public function get_cache_endpoint_response( array $params = [] ): WP_REST_Response {
		return ( new REST_API\Cache_Endpoint() )
			->get_route_response(
				$this->create_rest_request( $params )
			);
	}

	/**
	 * Test the permissions callback using user roles.
	 */
	public function test_permission_check_with_roles() {

		// Get an instance of the endpoint.
		$endpoint = new REST_API\Cache_Endpoint();

		// Create and get a user with the subscriber role.
		$subscriber = $this->factory->user->create_and_get();
		wp_set_current_user( $subscriber->ID );

		$this->assertEquals(
			false,
			$endpoint->permissions_check( $this->get_cache_endpoint_response() ),
			'Permission check for subscriber should have failed.'
		);

		// Create and get a user with the administrator role.
		$administrator = $this->factory->user->create_and_get();
		$administrator->add_role( 'administrator' );
		$administrator->remove_role( 'subscriber' );
		wp_set_current_user( $administrator->ID );

		$this->assertEquals(
			true,
			$endpoint->permissions_check( $this->get_cache_endpoint_response() ),
			'Permission check for administrator should have passed.'
		);
	}

	/**
	 * Test the permissions callback using the filter.
	 */
	public function test_permission_check_with_filter() {

		// Get an instance of the endpoint.
		$endpoint = new REST_API\Cache_Endpoint();

		add_filter( 'wp_irving_cache_endpoint_permissions_check', '__return_false' );

		$this->assertEquals(
			false,
			$endpoint->permissions_check( $this->get_cache_endpoint_response() ),
			'Permission check for filtered value should have failed.'
		);

		add_filter( 'wp_irving_cache_endpoint_permissions_check', '__return_true' );

		$this->assertEquals(
			true,
			$endpoint->permissions_check( $this->get_cache_endpoint_response() ),
			'Permission check for filtered value should have passed.'
		);
	}
}
