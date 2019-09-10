<?php
/**
 * WP Irving Cache.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Class to purge cache.
 */
class Purge_Cache {

	/**
	 * Slug to delete page/post cache
	 *
	 * @var string
	 */
	public $page_cache_slug = 'bust-endpoint-cache';

	/**
	 * Slug to wipe entire cache.
	 *
	 * @var string
	 */
	public $wipe_cache_slug = 'bust-entire-cache';

	/**
	 * Constructor for class.
	 */
	public function __construct() {
		add_action( 'wp_insert_post', [ $this, 'on_update_post' ], 10, 2 );
		add_action( 'transition_post_status', [ $this, 'on_post_status_transition' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'on_before_delete_post' ] );
	}

	/**
	 * Clear cache on post update.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function on_update_post( $post_id, $post ) : void {
		if ( 'publish' !== $post->post_status ) {
			return;
		}
		
		// Purge post redis cache.
	 	$this->fire_purge_request( $post_id );
	}

	/**
	 * Clear cache on page transition.
	 *
	 * @param string  $new_status New Status.
	 * @param string  $old_status Old Status.
	 * @param WP_Post $post       Post object.
	 */
	public function on_post_status_transition( $new_status, $old_status, $post ) : void {
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}

		// Purge post redis cache.
		$this->fire_purge_request( $post->ID );
	}

	/**
	 * Clear on post delete.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_before_delete_post( $post_id ) : void {
		// Purge post redis cache.
	 	$this->fire_purge_request( $post_id );
	}

	/**
	 * Fire purge request.
	 * 
	 * @param int $post_id Post ID.
	 */
	protected function fire_purge_request( $post_id ) : void {

		// Get the path.
		$path = wp_parse_url( get_the_permalink( $post_id ), PHP_URL_PATH );

		// Build the key.
		$key = sprintf( '%1$s,%2$s,%3$s',
			$path,
			'',
			'page'
		);

		// Build URL.
		$request_url = add_query_arg( [ 'endpoint' => $key ], 'http://192.168.50.1:3001/' . $this->page_cache_slug );
		// // home_url( '/bust-endpoint-cache' )

		// Fire the request.
		wp_remote_get( $request_url );
	}
}

new \WP_Irving\Purge_Cache();
