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
		$base_url = 'http://example.org/wp-json/irving/v1/components/';
		$params = [
			'path' => $path,
		];
		$full_url = add_query_arg( $base_url, $params );
		$request = new \WP_REST_Request( 'GET', $base_url );
		$request->set_query_params( $params );
		$_SERVER['REQUEST_URI'] = $full_url;

		return $request;
	}
}
