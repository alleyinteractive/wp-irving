<?php
/**
 * WP Irving Cache
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Class to purge cache.
 */
class Purge_Cache {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		// Register admin page.
		add_action( 'admin_menu', [ $this, 'register_admin' ] );

		// Purging actions.
		add_action( 'wp_insert_post', [ $this, 'on_update_post' ], 10, 2 );
		add_action( 'transition_post_status', [ $this, 'on_post_status_transition' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'on_before_delete_post' ] );

		add_action( 'init', [ $this, 'purge_cache_request' ] );
	}

	/**
	 * Clear cache on post update.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function on_update_post( $post_id, $post ) {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Purge cache.
		$this->fire_purge_request( get_the_permalink( $post_id ) );
	}

	/**
	 * Clear cache on page transition.
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
		$this->fire_purge_request( get_the_permalink( $post->ID ) );
	}

	/**
	 * Clear on post delete.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_before_delete_post( $post_id ) {
		$this->fire_purge_request( get_the_permalink( $post_id ) );
	}

	/**
	 * Fire purge request.
	 *
	 * @param string $permalink Permalink.
	 */
	protected function fire_purge_request( $permalink = '' ) {

		// Do not fire purges while importing or running PHPUnit tests.
		if (
			( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) ||
			( defined( 'WP_IRVING_TEST') && WP_IRVING_TEST )
		) {
			return;
		}

		// Bail early.
		if ( empty( $permalink ) ) {
			return;
		}

		// Get the path.
		$path = wp_parse_url( $permalink, PHP_URL_PATH );

		// Build the key.
		$key = sprintf( 'path=%1$s&context=site', $path );

		// Build URL.
		$request_url = add_query_arg(
			[
				'endpoint' => $key,
			],
			home_url( '/bust-endpoint-cache' )
		);

		// Fire the request to the irving cache.
		wp_remote_get( $request_url );

		// Fire the request to WordPress VIP.
		wp_remote_request(
			$permalink,
			[
				'method' => 'PURGE',
			]
		);
	}

	/**
	 * Purge cache on a PURGE request.
	 */
	public function purge_cache_request() {
		global $wp;

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $wp->request )
			&& ! empty( $_SERVER['REQUEST_METHOD'] )
			&& 'PURGE' === strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) {
			$this->fire_purge_request( home_url( $wp->request ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Fire wipe out request.
	 */
	protected function fire_wipe_request() {
		wp_remote_get( home_url( '/bust-entire-cache' ) );
	}

	/**
	 * Render the settings page.
	 */
	public function register_admin() {
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
	public function render() {

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated

		// Checking nonce.
		if ( isset( $_POST['irving-cache'] ) && ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'irving-cache' ) ) {
			wp_die( esc_html__( 'You should not be doing this.', 'wp-irving' ) );
		}

		// Firing request to clean cache.
		if ( isset( $_POST['submit'] ) ) {
			$this->fire_wipe_request();
		}

		// phpcs:enable WordPress.Security.NonceVerification.Missing
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'WP-Irving - Site Cache', 'wp-irving' ); ?>
			</h1>

			<hr class="wp-header-end">

			<form method="post">
				<?php settings_fields( 'irving-cache' ); ?>

				<h2>
					<?php esc_html_e( 'Clear Site Cache', 'wp-irving' ); ?>
				</h2>

				<p>
					<?php esc_html_e( 'Use with care. Clearing the entire site cache will negatively impact performance for a short period of time.', 'wp-irving' ); ?>
				</p>

				<?php submit_button( __( 'Clear Cache', 'wp-irving' ) ); ?>
			</form>

			<hr>
		</div>
		<?php
	}
}

add_action(
	'init',
	function() {
		new \WP_Irving\Purge_Cache();
	}
);
