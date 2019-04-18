<?php
/**
 * WP Irving integration for Safe Redirect Manager.
 *
 * @package WP_Irving;
 */

namespace WP_Irving;

/**
 * Class to parse redirects using the Safe Redirect Manager plugin.
 */
class Safe_Redirect_Manager {

	/**
	 * Store request parameters.
	 *
	 * @var array
	 */
	public $params = [];

	/**
	 * Constructor for class.
	 */
	public function __construct() {

		// Ensure Safe Redirect Manager exists and is enabled.
		if ( ! class_exists( '\SRM_Redirect' ) ) {
			return;
		}

		// Store the request parameters and kickoff the redirect check.
		add_action( 'wp_irving_handle_redirect', [ $this, 'parse_request' ] );
	}

	/**
	 * Store the request parameters.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public function parse_request( \WP_REST_Request $request ) {

		// Store all request parameters.
		$this->params = $request->get_params();

		// Filter the path and redirect values.
		add_filter( 'srm_requested_path', [ $this, 'set_srm_requested_path' ] );
		add_filter( 'srm_redirect_to', [ $this, 'set_srm_redirect_to' ] );

		$srm = new \SRM_Redirect();
		$srm->maybe_redirect();
	}

	/**
	 * Modify the requested path value to our path argument.
	 *
	 * @param  string $path Current path.
	 * @return string Path parameter.
	 */
	public function set_srm_requested_path( string $path ) : string {
		return $this->params['path'] ?? $path;
	}

	/**
	 * Modify the url to redirect to.
	 *
	 * @param string $path Redirect to url.
	 */
	public function set_srm_redirect_to( $path ) {

		// Get stored request params.
		$params = $this->params;

		// The path may be either a full URL, or a relative path.
		$path = wp_parse_url( $path, PHP_URL_PATH );

		// Replace request path with our redirect to path.
		$params['path'] = $path;

		// Build and return full API url.
		return add_query_arg( $params );
	}
}

add_action( 'init', function() {
	new \WP_Irving\Safe_Redirect_Manager();
} );
