<?php
/**
 * WP Irving integration for Safe Redirect Manager.
 *
 * @package WP_Irving;
 *
 * @see https://github.com/Automattic/WPCOM-Legacy-Redirector
 */

namespace WP_Irving;

/**
 * Class to parse redirects using the WPCOM Legacy Redirector plugin.
 */
class WPCOM_Legacy_Redirector {

	/**
	 * Constructor for class.
	 */
	public function __construct() {

		// Ensure WPCOM Legacy Redirector exists and is enabled.
		if (
			! class_exists( '\WPCOM_Legacy_Redirector' ) ||
			! method_exists( '\WPCOM_Legacy_Redirector', 'get_redirect_uri' )
		) {
			return;
		}

		// Handle Irving redirects.
		add_action( 'wp_irving_components_route', [ $this, 'handle_redirect' ], 10, 5 );
	}

	/**
	 * Find any matching redirect for requested path and include in response data.
	 *
	 * @param array     $data    WP Irving response data.
	 * @param \WP_Query $query   WP_Query object corresponding to this request.
	 * @param string    $context Request context (site or page).
	 * @param string    $path    Request path parameter.
	 * @param \WP_REST_Response $request  REST request.
	 */
	public function handle_redirect( $data, $query, $context, $path, $request ) : array {
		$params = $request->get_params();

		if ( empty( $params['path'] ) ) {
			return $data;
		}

		// Get the path parameter.
		$request_path = apply_filters( 'wpcom_legacy_redirector_request_path', $params['path'] );

		if ( $request_path ) {
			$redirect_uri = \WPCOM_Legacy_Redirector::get_redirect_uri( $request_path );

			if ( $redirect_uri ) {
				header( 'X-legacy-redirect: HIT' );
				$redirect_status = apply_filters( 'wpcom_legacy_redirector_redirect_status', 301, $redirect_uri );

				// The path may be either a full URL, or a relative path.
				if ( 0 === strpos( $redirect_uri, '/' ) ) {
					$params['path'] = wp_parse_url( $redirect_uri, PHP_URL_PATH );

					// Build the full URL.
					$redirect_to = add_query_arg( $params );
				} else {
					$redirect_to = $redirect_uri;
				}

				// Include redirect URL and status in response.
				$data['redirectURL'] = empty( $data['redirectURL'] ) ?
					$redirect_to ?? '' :
					$data['redirectURL'];
				$data['redirectStatus'] = empty( $data['redirectStatus'] ) ?
					$redirect_status ?? 0 :
					$data['redirectStatus'];

				return $data;
			}
		}

		return $data;
	}
}

add_action( 'init', function() {
	new \WP_Irving\WPCOM_Legacy_Redirector();
} );
