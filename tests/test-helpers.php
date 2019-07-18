<?php
/**
 * Class SRM_Redirects_Test
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Tests for Safe Redirect Maanger redirects
 */
class Test_Helpers {

	public function create_rest_request( $path ) {
		$full_path = add_query_arg(
			[
				'path' => $path,
			],
			'/wp-json/irving/v1/components/'
		);
		$request = new \WP_REST_Request( 'GET', $full_path );
		$_SERVER['REQUEST_URI'] = $full_path;

		return $request;
	}
}
