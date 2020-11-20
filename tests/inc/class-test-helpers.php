<?php
/**
 * Class Test_Helpers
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Tests for Safe Redirect Manager redirects
 */
class Test_Helpers {

	/**
	 * Create an instance of WP_REST_Request class with a given path.
	 *
	 * @param string $path Request path.
	 * @return WP_REST_Request
	 */
	public function create_rest_request( $path ) {
		$base_url = '/irving/v1/components';
		$params   = [
			'path' => $path,
		];
		$full_url = add_query_arg( $base_url, $params );
		$request  = new WP_REST_Request( 'GET', $base_url );
		$request->set_query_params( $params );
		$_SERVER['REQUEST_URI'] = $full_url;

		return $request;
	}

	/**
	 * Create a components endpoint response given a path.
	 *
	 * @param string $path Request path.
	 * @return WP_REST_Response
	 */
	public function get_components_endpoint_response( $path ) {
		$request  = $this->create_rest_request( $path );
		return rest_do_request( $request );
	}

	/**
	 * Helper used with the `upload_dir` filter to remove the /year/month sub directories from the uploads path and URL.
	 *
	 * Taken from the WP PHPUnit test helpers.
	 *
	 * @param array $uploads The uploads path data.
	 * @return array The altered array.
	 */
	public static function upload_dir_no_subdir( $uploads ) {
		$subdir = $uploads['subdir'];

		$uploads['subdir'] = '';
		$uploads['path']   = str_replace( $subdir, '', $uploads['path'] );
		$uploads['url']    = str_replace( $subdir, '', $uploads['url'] );

		return $uploads;
	}
}
