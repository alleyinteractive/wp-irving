<?php
/**
 * WP Irving integration for Safe Redirect Manager.
 *
 * @package WP_Irving;
 *
 * @see https://github.com/Automattic/WPCOM-Legacy-Redirector
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;
use WPCOM_Legacy_Redirector as Legacy_Redirector;

/**
 * Class to parse redirects using the WPCOM Legacy Redirector plugin.
 */
class WPCOM_Legacy_Redirector {
	use Singleton;

	/**
	 * Constructor for class.
	 */
	public function setup() {
		// Ensure WPCOM Legacy Redirector exists and is enabled.
		if (
			! class_exists( '\WPCOM_Legacy_Redirector' ) ||
			! method_exists( '\WPCOM_Legacy_Redirector', 'get_redirect_uri' )
		) {
			return;
		}

		// Handle Irving redirects.
		add_filter( 'wp_irving_components_redirect', [ $this, 'handle_redirect' ], 5, 2 );
	}

	/**
	 * Find any matching redirect for requested path and include in response data.
	 *
	 * @param array $data   An associative array with redirectTo (url) and redirectStatus (HTTP code, e.g. 301 or 302).
	 * @param array $params WP REST Request parameters.
	 * @return array Filtered WP Irving redirect data.
	 */
	public function handle_redirect( array $data, array $params ) : array {

		if ( empty( $params['path'] ) ) {
			return $data;
		}

		// Get the path parameter.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$request_path = apply_filters( 'wpcom_legacy_redirector_request_path', $params['path'] );

		if ( $request_path ) {
			// Look for an entry at the slashed version.
			$redirect_uri = Legacy_Redirector::get_redirect_uri( trailingslashit( $request_path ) );

			// If we don't find a hit, check the unslashed version.
			if ( empty( $redirect_uri ) ) {
				$redirect_uri = Legacy_Redirector::get_redirect_uri( rtrim( $request_path, '/' ) );
			}

			if ( $redirect_uri ) {
				if ( ! defined( 'WP_IRVING_TEST' ) || ! WP_IRVING_TEST ) {
					header( 'X-legacy-redirect: HIT' );
				}
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				$redirect_status = apply_filters( 'wpcom_legacy_redirector_redirect_status', 301, $redirect_uri );

				// The path may be either a full URL, or a relative path.
				if ( 0 === strpos( $redirect_uri, '/' ) ) {
					// Build a full URL.
					$redirect_to = home_url( $redirect_uri );
				} else {
					$redirect_to = $redirect_uri;
				}

				// Include redirect URL and status in response.
				$data['redirectTo']     = empty( $data['redirectTo'] ) ?
					$redirect_to ?? '' :
					$data['redirectTo'];
				$data['redirectStatus'] = empty( $data['redirectStatus'] ) ?
					$redirect_status ?? 0 :
					$data['redirectStatus'];

				return $data;
			}
		}

		return $data;
	}
}
