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
		if ( ! class_exists( '\WPCOM_Legacy_Redirector' ) ) {
			return;
		}

		// Store the request parameters and kickoff the redirect check.
		add_action( 'wp_irving_handle_redirect', [ $this, 'handle_redirect' ], 10, 3 );
	}

	/**
	 * Store the request parameters.
	 *
	 * @see based on \WPCOM_Legacy_Redirector::maybe_do_redirect()
	 *
	 * @param \WP_REST_Request $request  WP_REST_Request object.
	 * @param \WP_Query        $query    WP_Query object corresponding to this
	 *                                   request.
	 * @param string           $path     The path for this request.
	 */
	public function handle_redirect(
		\WP_REST_Request $request,
		\WP_Query $query,
		string $path
	) : void {

		// Store all request parameters.
		$params = $request->get_params();

		// Break apart the URL to get the path
		$url = wp_parse_url( $path, PHP_URL_PATH );
		$query_string = wp_parse_url( $path, PHP_URL_QUERY );

		if ( ! empty( $query_string ) ) {
			$url .= '?' . $query_string;
		}

		$request_path = apply_filters( 'wpcom_legacy_redirector_request_path', $url );

		if ( $request_path ) {
			$redirect_uri = \WPCOM_Legacy_Redirector::get_redirect_uri( $request_path );

			if ( $redirect_uri ) {
				header( 'X-legacy-redirect: HIT' );
				$redirect_status = apply_filters( 'wpcom_legacy_redirector_redirect_status', 301, $url );

				// The path may be either a full URL, or a relative path.
				$redirect_path = wp_parse_url( $redirect_uri, PHP_URL_PATH );

				// Replace request path with our redirect to path.
				$params['path'] = $redirect_path;

				// Build the full URL
				$rest_redirect_uri = add_query_arg( $params );

				wp_safe_redirect( $rest_redirect_uri, $redirect_status );
				exit;
			}
		}
	}
}

add_action( 'init', function() {
	new \WP_Irving\WPCOM_Legacy_Redirector();
} );
