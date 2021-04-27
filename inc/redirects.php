<?php
/**
 * Redirects.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Filter allowed redirect hosts to include the site_url, since only the
 * home_url is allowed by default.
 *
 * @param  array $hosts An array of allowed hosts.
 * @return array
 */
function allowed_redirect_hosts( $hosts ): array {
	$hosts[] = wp_parse_url( site_url(), PHP_URL_HOST );
	return $hosts;
}
add_filter( 'allowed_redirect_hosts', __NAMESPACE__ . '\allowed_redirect_hosts' );

/**
 * Manually call `old_slug_redirect_url`, check for a positive result, and then
 * hijack the response for Irving.
 *
 * @param array $data Irving components endpoint response.
 * @return array Updated response
 */
function handle_wp_old_slug_redirects( $data ) {

	// Possible url to redirect to.
	$redirect_link = '';

	/**
	 * The `wp_old_slug_redirect()` function handles the redirect internally,
	 * so we need to hook into this final filter, set it to a value that won't
	 * redirect, and also pass the real value back into our variable by
	 * reference.
	 *
	 * We don't need to remove this filter because it'll only execute once, and
	 * only during Irving component endpoint requests.
	 */
	add_filter(
		'old_slug_redirect_url',
		function( $link ) use ( &$redirect_link ) {
			$redirect_link = $link;
			return false;
		}
	);

	// Actually run the redirect logic.
	wp_old_slug_redirect(); // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_old_slug_redirect_wp_old_slug_redirect

	// If we returned a valid redirect link in our filter, let's pass it to
	// Irving.
	if ( ! empty( $redirect_link ) ) {
		$data['redirectTo']     = $redirect_link;
		$data['redirectStatus'] = 301;
	}

	return $data;
}
add_filter( 'wp_irving_components_route', __NAMESPACE__ . '\handle_wp_old_slug_redirects' );
