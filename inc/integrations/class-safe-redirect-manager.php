<?php
/**
 * WP Irving integration for Safe Redirect Manager.
 *
 * @package WP_Irving;
 */

namespace WP_Irving\Integrations;

use SRM_Redirect;
use WP_Irving\Singleton;

/**
 * Class to parse redirects using the Safe Redirect Manager plugin.
 */
class Safe_Redirect_Manager {
	use Singleton;

	/**
	 * Reference to Safe Redirect Manager SRM_Redirect singleton instance.
	 *
	 * @var SRM_Redirect
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
		if ( ! is_callable( [ 'SRM_Redirect', 'match_redirect' ] ) ) {
			return;
		}

		$this->srm = SRM_Redirect::factory();

		// Remove redirect actions from SRM.
		remove_action( 'parse_request', [ $this->srm, 'maybe_redirect' ], 0 );
		remove_action( 'template_redirect', [ $this->srm, 'maybe_redirect' ], 0 );

		// Re-use SRM's filter to redirect only on 404s.
		add_filter( 'wp_irving_components_redirect', [ $this, 'get_srm_redirect' ], 5, 2 );
	}

	/**
	 * Find any matching redirect for requested path and include in response data.
	 *
	 * @param array $data   WP Irving response data.
	 * @param array $params Request parameters.
	 */
	public function get_srm_redirect( $data, $params ): array {
		// Store request path.
		$this->params = $params;

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
