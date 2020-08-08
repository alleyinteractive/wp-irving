<?php
/**
 * Class Test_Class_Data_Endpoint.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\REST_API;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_UnitTestCase;

/**
 * Test the data endpoint functionality.
 *
 * @group endpoints
 */
class Test_Class_Data_Endpoint extends WP_UnitTestCase {

	/**
	 * Class setup.
	 */
	public static function wpSetUpBeforeClass() {
		add_filter(
			'wp_irving_data_endpoints',
			function( $data ) {
				return [
					[
						'slug'     => 'return-true',
						'callback' => function() {
							return [ 'Hello World' ];
						},
					],
					[
						'slug'                => 'fail-permissions-check',
						'callback'            => '__return_true',
						'permission_callback' => '__return_false',
					],
				];
			}
		);
	}

	/**
	 * Create an instance of WP_REST_Request class with a given path.
	 *
	 * @param string $data_slug Slug of the data endpoint.
	 * @return WP_REST_Request
	 */
	public function create_data_endpoint_rest_request( string $data_slug ): WP_REST_Request {
		return new WP_REST_Request( 'GET', '/irving/v1/data/' . $data_slug );
	}

	/**
	 * Get the response from a hit on the data endpoint.
	 *
	 * @param string $data_slug Slug of the data endpoint.
	 * @return array
	 */
	public function get_data_endpoint_response( string $data_slug ): array {
		return rest_do_request( $this->create_data_endpoint_rest_request( $data_slug ) )->get_data();
	}

	/**
	 * Test the `permission_callback` for the data endpoint.
	 */
	public function test_permission_callback() {
		$this->assertEquals(
			[
				'Hello World',
			],
			$this->get_data_endpoint_response( 'return-true' ),
			'Endpoint did not return the right data.'
		);

		$this->assertEquals(
			[
				'code'    => 'rest_forbidden',
				'message' => 'Sorry, you are not allowed to do that.',
				'data'    => [
					'status' => '401',
				],
			],
			$this->get_data_endpoint_response( 'fail-permissions-check' ),
			'Permissions check did not fail.'
		);
	}
}
