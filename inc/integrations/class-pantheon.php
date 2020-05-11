<?php
/**
 * WP Irving integration for VIP Go.
 *
 * @package WP_Irving;
 */

namespace WP_Irving;

/**
 * Class to handle modifications specific to VIP Go.
 */
class Pantheon {

	/**
	 * Constructor for class.
	 */
	public function __construct() {
		add_action( 'admin_post_pantheon_cache_flush_site', [ $this, 'pantheon_flush_site' ], 9 );
	}

	/**
	 * Clear the cache for the entire site.
	 *
	 * @return void
	 */
	public function pantheon_flush_site() {
		if ( ! function_exists( 'current_user_can' ) || false == current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! empty( $_POST['pantheon-cache-nonce'] ) && wp_verify_nonce( $_POST['pantheon-cache-nonce'], 'pantheon-cache-clear-all' ) ) {
			\WP_Irving\Cache::instance()->fire_wipe_request();
		}
	}
}

add_action(
	'init',
	function() {
		new \WP_Irving\Pantheon();
	}
);
