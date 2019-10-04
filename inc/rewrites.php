<?php
/**
 * Rewrites.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Adds additional rest routes to support a different home_url value.
 *
 * @param array $rules Rewrite rules.
 * @return array
 */
function add_additional_rest_routes( $rules ) {

	$site_paths = get_site_paths();
	$prefix     = rest_get_url_prefix();

	foreach ( $site_paths as $path ) {
		$rest_api = [
			"^{$path}/{$prefix}/?$"              => 'index.php?rest_route=/',
			"^{$path}/{$prefix}/(.*)?"           => 'index.php?rest_route=/$matches[1]',
			"^index.php/{$path}/{$prefix}/?$"    => 'index.php?rest_route=/',
			"^index.php/{$path}/{$prefix}/(.*)?" => 'index.php?rest_route=/$matches[1]',
		];

		$rules = array_merge( $rest_api, $rules );
	}

	return $rules;
}
add_filter( 'rewrite_rules_array', __NAMESPACE__ . '\add_additional_rest_routes' );

/**
 * Get all site paths for a multisite.
 *
 * @return array
 */
function get_site_paths(): array {

	if ( ! is_multisite() ) {
		return [];
	}

	$sites = get_sites();
	$paths = wp_list_pluck( $sites, 'path' );
	return array_filter(
		array_map(
			function ( $path ) {
				return trim( $path, '/' );
			},
			$paths
		)
	);
}
