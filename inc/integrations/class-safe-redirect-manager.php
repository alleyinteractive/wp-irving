<?php
/**
 * WP Irving integration for Safe Redirect Manager.
 *
 * @package WP_Irving;
 */

namespace WP_Irving\Integrations;

/**
 * Class to parse redirects using the Safe Redirect Manager plugin.
 */
class Safe_Redirect_Manager {

	/**
	 * Class instance.
	 *
	 * @var null|self
	 */
	protected static $instance;

	/**
	 * Get class instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

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
	public function setup() {
		// Ensure Safe Redirect Manager exists and is enabled.
		if ( ! class_exists( '\SRM_Redirect' ) || ! method_exists( '\SRM_Redirect', 'match_redirect' ) ) {
			return;
		}

		$this->srm = \SRM_Redirect::factory();

		// Remove redirect actions from SRM.
		remove_action( 'parse_request', [ $this->srm, 'maybe_redirect' ], 0 );
		remove_action( 'template_redirect', [ $this->srm, 'maybe_redirect' ], 0 );

		// Re-use SRM's filter to redirect only on 404s.
		add_filter( 'wp_irving_components_route', [ $this, 'get_srm_redirect' ], 5, 5 );
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
	public function get_srm_redirect( $data, $query, $context, $path, $request ): array {
		// Store request path.
		$this->params = $request->get_params();

		// Filter the redirect value.
		add_filter( 'srm_redirect_to', [ $this, 'set_srm_redirect_to' ] );

		// Find matching redirect for current path.
		$redirect_match = $this->srm->match_redirect( untrailingslashit( $this->params['path'] ) );

		// Add redirect_to and status_code from SRM match.
		$data['redirectTo']     = empty( $data['redirectTo'] ) ?
			$redirect_match['redirect_to'] ?? '' :
			$data['redirectTo'];
		$data['redirectStatus'] = empty( $data['redirectStatus'] ) ?
			$redirect_match['status_code'] ?? 0 :
			$data['redirectStatus'];

		return $data;
	}

	/**
	 * Modify the url to redirect to.
	 *
	 * @param string $path Redirect to url.
	 */
	public function set_srm_redirect_to( $path ) {
		// The path may be either a full URL, or a relative path.
		if ( 0 === strpos( $path, '/' ) ) {
			// Build a full URL.
			return home_url( $path );
		}

		return $path;
	}
}
