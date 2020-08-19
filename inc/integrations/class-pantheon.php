<?php
/**
 * WP Irving integration for VIP Go.
 *
 * @package WP_Irving;
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;

/**
 * Class to handle modifications specific to Pantheon.
 */
class Pantheon {
	use Singleton;

	/**
	 * Constructor for class.
	 */
	public function setup() {
		if ( ! function_exists( 'pantheon_wp_clear_edge_paths' ) ) {
			return;
		}

		add_action( 'admin_post_pantheon_cache_flush_site', [ $this, 'pantheon_flush_site' ], 9 );

		// Post purging actions.
		add_action( 'wp_irving_cache_pre_purge', [ $this, 'purge_endpoint_urls' ], 10, 2 );

	}

	/**
	 * Hook into core cache and purge endpoint caches on pantheon as well.
	 *
	 * @param array $purge_queue Array of URLs about to be purged in Redis.
	 */
	public function purge_endpoint_urls( $purge_queue ) {
		$purge_urls = $this->convert_urls_to_endpoint_paths( $purge_queue );
		pantheon_wp_clear_edge_paths( $purge_urls );
	}

	/**
	 * Convert permalinks into Irving endpoint URLs.
	 *
	 * @param array $urls Permalinks to purge.
	 * @return array
	 */
	public function convert_urls_to_endpoint_paths( $urls ) {
		$endpoint_urls = [];

		foreach ( $urls as $url ) {
			$endpoint_urls[] = \WP_Irving\REST_API\Components_Endpoint::get_wp_irving_api_url( $url, 'site' );
			$endpoint_urls[] = \WP_Irving\REST_API\Components_Endpoint::get_wp_irving_api_url( $url, 'page' );
		}

		// Only return paths.
		return array_map(
			function ( $endpoint ) {
				return str_replace( site_url(), '', $endpoint );
			},
			$endpoint_urls
		);
	}

	/**
	 * Clear the cache for the entire site.
	 *
	 * @return bool
	 */
	public function pantheon_flush_site() {
		if ( ! function_exists( 'current_user_can' ) || false == current_user_can( 'manage_options' ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( ! empty( $_POST['pantheon-cache-nonce'] ) && wp_verify_nonce( $_POST['pantheon-cache-nonce'], 'pantheon-cache-clear-all' ) ) {
			\WP_Irving\Cache::instance()->fire_wipe_request();
		}
	}
}
