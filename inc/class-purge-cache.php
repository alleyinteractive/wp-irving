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
	 * Settings ID.
	 *
	 * @var string
	 */
	protected $id = 'irving-cache';

	/**
	 * Page ID.
	 *
	 * @var string
	 */
	protected $page = 'wp-irving';

	/**
	 * Constructor for class.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin' ] );

		// Purging actions.
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

		// Purge cache.
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

		// Purge cache.
		$this->fire_purge_request( $post->ID );
	}

	/**
	 * Clear on post delete.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_before_delete_post( $post_id ) : void {
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
		$request_url = add_query_arg( [ 'endpoint' => $key ], home_url( '/bust-endpoint-cache' ) );

		// Fire the request.
		wp_remote_get( $request_url );
	}

	/**
	 * Fire wipe out request.
	 */
	protected function fire_wipe_request() : void {
		wp_remote_get( '/bust-entire-cache' );
	}

	/**
	 * Render the settings page.
	 */
	public function register_admin() : void {
		add_submenu_page(
			'options-general.php',
			__( 'WP-Irving Cache', 'wp-irving' ),
			__( 'WP-Irving Cache', 'wp-irving' ),
			'manage_options',
			'wp-irving-cache',
			[ $this, 'render' ]
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render() : void {
		// Firing request to clean cache.
		if ( isset( $_POST['submit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->fire_wipe_request();
		}
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'WP-Irving - Site Cache', 'wp-irving' ); ?>
			</h1>

			<hr class="wp-header-end">

			<form method="post">
				<?php settings_fields( $this->id ); ?>

				<h3>
					<?php esc_attr_e( 'Clear Site Cache', 'wp-irving' ); ?>
				</h3>

				<p>
					<?php esc_attr_e( 'Use with care. Clearing the entire site cache will negatively impact performance for a short period of time.', 'wp-irving' ); ?>
				</p>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button" value="<?php echo esc_attr( 'Clear Cache', 'wp-irving' ); ?>">
				</p>
			</form>

			<hr>
		</div>
		<?php
	}
}

new \WP_Irving\Purge_Cache();
