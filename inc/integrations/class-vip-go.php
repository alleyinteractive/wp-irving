<?php
/**
 * WP Irving integration for VIP Go.
 *
 * @package WP_Irving;
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;

/**
 * Class to handle modifications specific to VIP Go.
 */
class VIP_Go {
	use Singleton;

	/**
	 * Constructor for class.
	 */
	public function setup() {

		if ( ! ( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV ) ) {
			return;
		}

		add_filter( 'wpcom_vip_cache_purge_urls', [ $this, 'wpcom_vip_cache_purge_urls' ], 10, 2 );
	}

	/**
	 * Add to the list of URLs to purge when a post is modified.
	 *
	 * @param array $purge_urls URLs to purge.
	 * @param int   $post_id    The ID of the post triggering the purge.
	 * @return array
	 */
	public function wpcom_vip_cache_purge_urls( $purge_urls, $post_id ) {

		$new_urls = [];

		// Get the components endpoint URL equivalents for the current list of purge URLs.
		foreach ( $purge_urls as $url ) {

			if ( false === strpos( $url, home_url() ) ) {
				continue;
			}

			$new_urls[] = \WP_Irving\REST_API\Components_Endpoint::get_wp_irving_api_url( $url, 'site' );
			$new_urls[] = \WP_Irving\REST_API\Components_Endpoint::get_wp_irving_api_url( $url, 'page' );
		}

		// Do a hacky replace on the URLs.
		// @todo remove this once the VIP cache manager plugin is patched.
		$new_urls = array_map(
			function( $url ) {
				return str_replace( '?', '??', $url );
			},
			$new_urls
		);

		// Add the URLs to the purge list.
		$purge_urls = array_merge( $new_urls, $purge_urls );

		return $purge_urls;
	}
}
