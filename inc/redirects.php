<?php
/**
 * Class file for Components endpoint.
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
function allowed_redirect_hosts( $hosts ) {
	$hosts[] = wp_parse_url( site_url(), PHP_URL_HOST );
	return $hosts;
}
add_filter( 'allowed_redirect_hosts', __NAMESPACE__ . '\allowed_redirect_hosts' );
