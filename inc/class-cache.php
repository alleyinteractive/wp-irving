<?php
/**
 * WP Irving Cache
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Class for managing caches in Irving.
 */
class Cache {

	/**
	 * Class instance.
	 *
	 * @var null|self
	 */
	protected static $instance;

	/**
	 * Get class instance
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
			static::$instance->setup();
		}
		return static::$instance;
	}

	/**
	 * Class constructor.
	 */
	public function setup() {
		// Register admin page.
		add_action( 'admin_menu', [ $this, 'register_admin' ] );

		// Purging actions.
		add_action( 'wp_insert_post', [ $this, 'on_update_post' ], 10, 2 );
		add_action( 'clean_post_cache', [ $this, 'on_clean_post_cache' ], 10, 2 );
		add_action( 'transition_post_status', [ $this, 'on_post_status_transition' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'on_before_delete_post' ] );
		add_action( 'delete_attachment', [ $this, 'on_delete_attachment' ] );

		add_action( 'init', [ $this, 'purge_cache_request' ] );
	}

	/**
	 * Fire purge request.
	 *
	 * @param string $permalink Permalink.
	 */
	public function fire_purge_request( $permalink = '' ) {
		// Do not fire purges while importing.
		if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
			return;
		}

		// Bail early.
		if ( empty( $permalink ) ) {
			return;
		}

		// Fire the request to bust both VIP and Irving Redis cache.
		wp_remote_request( $permalink, [ 'method' => 'PURGE' ] );
	}

	/**
	 * Fire wipe out request.
	 */
	public function fire_wipe_request() {
		wp_remote_get( home_url( '/purge-cache' ) );
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
	 * Clear cache on post cache clear.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function on_clean_post_cache( $post_id, $post ) {
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
	 * Clear attachment on delete.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_delete_attachment( $post_id ) {
		$this->fire_purge_request( get_the_permalink( $post_id ) );
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
		( new \WP_Irving\Cache )->instance();
	}
);
