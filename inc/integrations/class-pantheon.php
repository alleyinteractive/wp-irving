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
		if ( ! function_exists( 'pantheon_wp_clear_edge_paths' ) ) {
			return;
		}

		add_action( 'admin_post_pantheon_cache_flush_site', [ $this, 'pantheon_flush_site' ], 9 );

		// Post purging actions.
		add_action( 'wp_insert_post', [ $this, 'on_update_post' ], 10, 2 );
		add_action( 'clean_post_cache', [ $this, 'on_clean_post_cache' ], 10, 2 );
		add_action( 'transition_post_status', [ $this, 'on_post_status_transition' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'on_before_delete_post' ] );
		add_action( 'delete_attachment', [ $this, 'on_delete_attachment' ] );

		// Term purging actions.
		add_action( 'created_term', [ $this, 'on_created_term' ], 10, 3 );
		add_action( 'edited_term', [ $this, 'on_edited_term' ] );
		add_action( 'delete_term', [ $this, 'on_delete_term' ] );
		add_action( 'clean_term_cache', [ $this, 'on_clean_term_cache' ] );

		// User purging actions.
		add_action( 'clean_user_cache', [ $this, 'on_clean_user_cache' ] );
	}

	/**
	 * Purge cache on post update.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function on_update_post( $post_id, $post ) {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Purge cache.
		$this->get_and_purge_post_endpoints( $post_id );
	}

	/**
	 * Purge cache on post cache clear.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function on_clean_post_cache( $post_id, $post ) {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Purge cache.
		$this->get_and_purge_post_endpoints( $post_id );
	}

	/**
	 * Purge cache on page transition.
	 *
	 * @param string  $new_status New Status.
	 * @param string  $old_status Old Status.
	 * @param WP_Post $post       Post object.
	 */
	public function on_post_status_transition( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}

		// Purge cache.
		$this->get_and_purge_post_endpoints( $post );
	}

	/**
	 * Purge on post delete.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_before_delete_post( $post_id ) {
		$this->get_and_purge_post_endpoints( $post_id );
	}

	/**
	 * Purge attachment on delete.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_delete_attachment( $post_id ) {
		$this->get_and_purge_post_endpoints( $post_id );
	}

	/**
	 * Purge term cache on create.
	 *
	 * @param int\WP_Term $term_id Term ID.
	 */
	public function on_created_term( $term_id ) {
		$this->get_and_purge_term_endpoints( $term_id );
	}

	/**
	 * Purge term cache on edit.
	 *
	 * @param int\WP_Term $term_id Term ID.
	 */
	public function on_edited_term( $term_id ) {
		$this->get_and_purge_term_endpoints( $term_id );
	}

	/**
	 * Purge term cache on delete.
	 *
	 * @param int\WP_Term $term_id Term ID.
	 */
	public function on_deleted_term( $term_id ) {
		$this->get_and_purge_term_endpoints( $term_id );
	}

	/**
	 * Purge terms on clean cache.
	 *
	 * @param array $term_ids Term IDs.
	 */
	public function on_clean_term_cache( $term_ids ) {
		$ids = is_array( $term_ids ) ? $term_ids : array( $term_ids );

		foreach ( $ids as $term_id ) {
			$this->get_and_purge_term_endpoints( $term_id );
		}
	}

	/**
	 * Purge user on clean cache.
	 *
	 * @param array $user_id User ID.
	 */
	public function on_clean_user_cache( $user_id ) {
		$this->get_and_purge_user_endpoints( $user_id );
	}

	/**
	 * Get and purge post endpoints.
	 *
	 * @param int\WP_Post $post Post object or ID.
	 */
	public function get_and_purge_post_endpoints( $post ) {
		$purge_urls = $this->convert_urls_to_endpoints( \WP_Irving\Cache::instance()->get_post_purge_urls( $post ) );
		pantheon_wp_clear_edge_paths( $purge_urls );
	}

	/**
	 * Get and purge term endpoints.
	 *
	 * @param int\WP_Term $term Term object or ID.
	 */
	public function get_and_purge_term_endpoints( $term ) {
		$purge_urls = $this->convert_urls_to_endpoints( \WP_Irving\Cache::instance()->get_term_purge_urls( $term ) );
		pantheon_wp_clear_edge_paths( $purge_urls );
	}

	/**
	 * Get and purge user endpoints.
	 *
	 * @param int\WP_User $user user object or ID.
	 */
	public function get_and_purge_user_endpoints( $user ) {
		$purge_urls = $this->convert_urls_to_endpoints( \WP_Irving\Cache::instance()->get_user_purge_urls( $user ) );
		pantheon_wp_clear_edge_paths( $purge_urls );
	}

	/**
	 * Convert permalinks into Irving endpoint URLs.
	 *
	 * @param array $urls Permalinks to purge.
	 * @return array
	 */
	public function convert_urls_to_endpoints( $urls ) {
		$endpoint_urls = [];

		foreach ( $urls as $url ) {
			if ( false === strpos( $url, home_url() ) ) {
				continue;
			}

			$endpoint_urls[] = \WP_Irving\REST_API\Components_Endpoint::get_wp_irving_api_url( $url, 'site' );
			$endpoint_urls[] = \WP_Irving\REST_API\Components_Endpoint::get_wp_irving_api_url( $url, 'page' );
		}

		return $endpoint_urls;
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
