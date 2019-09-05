<?php
/**
 * WP Irving Page Cache.
 *
 * @package WP_Irving;
 */

namespace WP_Irving;

/**
 * Class to add Purge Page Cache support.
 */
class Purge_Page_Cache {

	/**
	 * Constructor for class.
	 */
	public function __construct() {
		add_action( 'wp_insert_post', [ $this, 'on_update_post' ], 10, 2 );
		add_action( 'transition_post_status', [ $this, 'on_post_status_transition' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'on_before_delete_post' ] );
	}

	/**
	 * Clear redis cache from the front-end on post/page update.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function on_update_post( $post_id, $post ) {
		if ( 'publish' !== $post->post_status ) {
			return;
		}
		
		// Purge post redis cache.
	 	$this->fire_purge_request( $post_id );
	}

	/**
	 * Clear redis cache from the front-end on page transition.
	 *
	 * @param string  $new_status New Status.
	 * @param string  $old_status Old Status.
	 * @param WP_Post $post       Post object.
	 */
	public function on_post_status_transition( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}

		// Purge post redis cache.
		$this->fire_purge_request( $post->ID );
	}

	/**
	 * Clear redis cache from the front-end on post/page delete.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_before_delete_post( $post_id ) {
		// Purge post redis cache.
	 	$this->fire_purge_request( $post_id );
	}

	/**
	 * Fire purge request to the front-end app.
	 * 
	 * @param int $post_id Post ID.
	 */
	protected function fire_purge_request( $post_id ) {
		// Build URL.
		$request_url = add_query_arg(
			[
				'endpoint'  => $this->get_endpoint( $post_id ),
			],
			'http://192.168.50.1:3001/bust-cache' // home_url( '/bust-cache' )
		);

		// Fire the request.
		wp_remote_get( $request_url );
	}

	/**
	 * Get Irving endpoint with post/page info.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	protected function get_endpoint( $post_id ) : string {

		// Get post permalink.
		$permalink = get_the_permalink( $post_id );

		// Parse the path.
		$path = wp_parse_url( $permalink, PHP_URL_PATH );

		// Apply path to base components endpoint.
		$api_url = add_query_arg(
			'path',
			$path,
			rest_url( 'irving/v1/components/' )
		);

		// Filter as if it were the post row.
		return apply_filters( 'wp_irving_post_row_action_path_url', $api_url, get_post( $post_id ) );
	}
}

new \WP_Irving\Purge_Page_Cache();
