<?php
/**
 * Class file for endpoint.
 *
 * @package WP_Irving
 */

namespace WP_Irving\REST_API;

/**
 * Endpoint.
 */
class Endpoint {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'irving/v1';

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Resolve CORS issues
		add_filter( 'allowed_http_origin', '__return_true' );
		header( 'Access-Control-Allow-Origin: *' );

		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}
}
