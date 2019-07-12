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
	 * Reference to Safe Redirect Manager SRM_Redirect singleton instance.
	 *
	 * @var array
	 */
	private $srm;

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

		$this->srm = \SRM_Redirect::factory();

		// Remove redirect actions from SRM.
		remove_action( 'parse_request', [ $this->srm, 'maybe_redirect' ], 0 );
		remove_action( 'template_redirect', [ $this->srm, 'maybe_redirect' ], 0 );

		// Re-use SRM's filter to redirect only on 404s.
		add_filter( 'wp_irving_components_route', [ $this, 'get_srm_redirect' ], 10, 5 );
	}

	/**
	 * Find any matching redirect for requested path and include in response data.
	 *
	 * @param array             $data     WP Irving response data.
	 * @param \WP_Query         $query    WP_Query object corresponding to this request.
	 * @param string            $context  Request context (site or page).
	 * @param string            $path     Request path parameter.
	 * @param \WP_REST_Response $request  REST request.
	 */
	public function get_srm_redirect( $data, $query, $context, $path, $request ) : array {
		// Store request path.
		$this->params = $request->get_params();

		// Filter the path and redirect values.
		add_filter( 'srm_requested_path', [ $this, 'set_srm_requested_path' ] );
		add_filter( 'srm_redirect_to', [ $this, 'set_srm_redirect_to' ] );

		// Find matching redirect for current path.
		$this->srm->get_redirect_match();

		// Add SRM redirect
		$data['redirectURL'] = empty( $data['redirectURL'] ) ?
			$this->srm->redirect_to ?? '' :
			$data['redirectURL'];
		$data['redirectStatus'] = empty( $data['redirectStatus'] ) ?
			$this->srm->status_code ?? 0 :
			$data['redirectStatus'];

		return $data;
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
		if ( 0 === strpos( $path, '/' ) ) {
			$path = wp_parse_url( $path, PHP_URL_PATH );
		} else {
			return $path;
		}
		// Replace request path with our redirect to path.
		$params['path'] = $path;

		// Build and return full API url.
		return add_query_arg( $params );
	}
}

add_action( 'init', function() {
	new \WP_Irving\Safe_Redirect_Manager();
} );
