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
	 * Array of URLs to purge.
	 *
	 * @var array
	 */
	public static $purge_queue = [];

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

		// Post purging actions.
		add_action( 'wp_insert_post', [ $this, 'purge_post_if_published' ], 10, 2 );
		add_action( 'clean_post_cache', [ $this, 'purge_post_if_published' ], 10, 2 );
		add_action( 'transition_post_status', [ $this, 'on_post_status_transition' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'purge_post_by_id' ] );
		add_action( 'delete_attachment', [ $this, 'purge_post_by_id' ] );

		// Term purging actions.
		add_action( 'created_term', [ $this, 'purge_term_by_id' ] );
		add_action( 'edited_term', [ $this, 'purge_term_by_id' ] );
		add_action( 'delete_term', [ $this, 'purge_term_by_id' ] );
		add_action( 'clean_term_cache', [ $this, 'purge_terms' ] );

		// User purging actions.
		add_action( 'clean_user_cache', [ $this, 'purge_user_by_id' ] );

		add_action( 'init', [ $this, 'purge_cache_request' ] );
		add_action( 'shutdown', array( $this, 'fire_purge_request' ) );
	}

	/**
	 * Purge cache on post update.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function purge_post_if_published( $post_id, $post ) {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Purge cache.
		$this->purge_post_by_id( $post_id );
	}

	/**
	 * Purge cache on post status transition.
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
		$this->purge_post_by_id( $post->ID );
	}

	/**
	 * Purge post cache by ID.
	 *
	 * @param int $post_id Post ID.
	 */
	public function purge_post_by_id( $post_id ) {
		$this->queue_post_purge_requests( $post_id );
	}

	/**
	 * Purge term cache by ID.
	 *
	 * @param int\WP_Term $term_id Term ID.
	 */
	public function purge_term_by_id( $term_id ) {
		$this->queue_term_purge_requests( $term_id );
	}

	/**
	 * Purge terms on clean cache.
	 *
	 * @param array $term_ids Term IDs.
	 */
	public function purge_terms( $term_ids ) {
		$ids = is_array( $term_ids ) ? $term_ids : array( $term_ids );

		foreach ( $ids as $term_id ) {
			$this->purge_term_by_id( $term_id );
		}
	}

	/**
	 * Purge user on clean cache.
	 *
	 * @param array $user_id User ID.
	 */
	public function purge_user_by_id( $user_id ) {
		$this->queue_user_purge_requests( $user_id );
	}

	/**
	 * Get an array of URLS to purge for a post.
	 *
	 * @param int|\WP_Post $post_id A WP Post object or a post ID.
	 * @return array Purge URLs
	 */
	public function get_post_purge_urls( $post_id ) : array {
		$post_purge_urls = [];

		if ( defined( 'WP_IMPORTING' ) && true === WP_IMPORTING ) {
			return $post_purge_urls;
		}

		$current_post = get_post( $post_id );

		if (
			empty( $current_post )
			|| 'revision' === $current_post->post_type
			|| ! in_array( get_post_status( $current_post ), array( 'publish', 'inherit', 'trash' ), true )
			|| ! is_post_type_viewable( $current_post->post_type )
			// Skip purge if it is a new attachment.
			|| ( 'attachment' === $current_post->post_type && $current_post->post_date === $current_post->post_modified )
		) {
			return $post_purge_urls;
		}

		$post_purge_urls[] = get_permalink( $current_post );
		$post_purge_urls[] = home_url( '/' );

		// Don't just purge the attachment page, but also include the file itself.
		if ( 'attachment' === $current_post->post_type ) {
			$post_purge_urls[] = wp_get_attachment_url( $current_post->ID );
		}

		$taxonomies = get_object_taxonomies( $current_post, 'object' );

		foreach ( $taxonomies as $taxonomy ) {
			if ( true !== $taxonomy->public ) {
				continue;
			}

			$taxonomy_name = $taxonomy->name;
			$terms         = get_the_terms( $current_post, $taxonomy_name );

			if ( false === $terms ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$post_purge_urls = array_merge( $post_purge_urls, $this->get_single_term_purge_urls( $term ) );
			}
		}

		// Purge author urls.
		$post_author_id  = get_post_field( 'post_author', $current_post );
		$post_purge_urls = array_merge( $post_purge_urls, $this->get_user_purge_urls( $post_author_id ) );

		/**
		 * Filter array of URLs to purge when a post action is triggered.
		 *
		 * @param array $post_purge_urls List of URLs to purge for this post.
		 */
		return apply_filters( 'wp_irving_cache_post_purge_urls', $post_purge_urls );
	}

	/**
	 * Get an array of URLS to purge for terms. This will handle hierarchical terms as well.
	 *
	 * @param int|\WP_Term $term A WP Term object, or a term ID.
	 * @return array Purge URLs
	 */
	public function get_term_purge_urls( $term ) : array {
		$term_purge_urls = [];
		$term = get_term( $term );

		if ( is_wp_error( $term ) || empty( $term ) || ( defined( 'WP_IMPORTING' ) && true === WP_IMPORTING ) ) {
			return $term_purge_urls;
		}

		// Before adding term to purge URLs, make sure we actually need to do it.
		$term_ids        = [ $term->term_id ];
		$taxonomy_object = get_taxonomy( $term->taxonomy );

		if (
			! $taxonomy_object
			|| (
				false === $taxonomy_object->public
				&& false === $taxonomy_object->publicly_queryable
				&& false === $taxonomy_object->show_in_rest
			)
		) {
			return $term_purge_urls;
		}

		$get_term_args = array(
			'taxonomy'    => $term->taxonomy,
			'include'     => $term_ids,
			'hide_empty'  => false,
		);
		$terms = get_terms( $get_term_args );

		if ( is_wp_error( $terms ) ) {
			return $term_purge_urls;
		}

		foreach ( $terms as $term ) {
			$term_purge_urls = array_merge( $term_purge_urls, $this->get_single_term_purge_urls( $term ) );
		}

		/**
		 * Filter array of URLs to purge when a term action is triggered.
		 *
		 * @param array $term_purge_urls List of URLs to purge for this term.
		 */
		return apply_filters( 'wp_irving_cache_term_purge_urls', array_unique( $term_purge_urls ) );
	}

	/**
	 * Get all URLs to be purged for a single term.
	 *
	 * @param  WP_Term $term A WP term object.
	 * @return array An array of URLs to be purged
	 */
	public function get_single_term_purge_urls( $term ) : array {
		$term_purge_urls = [];

		// Purge term archive.
		$taxonomy_name   = $term->taxonomy;
		$maybe_purge_url = get_term_link( $term, $taxonomy_name );

		if ( ! empty( $maybe_purge_url ) && ! is_wp_error( $maybe_purge_url ) && is_string( $maybe_purge_url ) ) {
			$term_purge_urls[] = $maybe_purge_url;
		}

		// Purge term feed.
		$maybe_purge_feed_url = get_term_feed_link( intval( $term->term_id ), $taxonomy_name );

		if ( false !== $maybe_purge_feed_url ) {
			$term_purge_urls[] = $maybe_purge_feed_url;
		}

		return $term_purge_urls;
	}

	/**
	 * Get all URLs to be purged for a given term
	 *
	 * @param int|\WP_User $user User object or ID.
	 * @return array An array of URLs to be purged.
	 */
	public function get_user_purge_urls( $user ) : array {
		$user_purge_urls = [];

		if (
			empty( $user )
			|| ( ! $user instanceof \WP_User && ! is_numeric( $user ) )
			|| ( defined( 'WP_IMPORTING' ) && true === WP_IMPORTING )
		) {
			return $user_purge_urls;
		}

		// Purge user archive.
		$user_id         = ( $user instanceof \WP_User ) ? $user->ID : $user;
		$maybe_purge_url = get_author_posts_url( $user_id );

		if ( ! empty( $maybe_purge_url ) && ! is_wp_error( $maybe_purge_url ) && is_string( $maybe_purge_url ) ) {
			$user_purge_urls[] = $maybe_purge_url;
		}

		// Purge user feeds.
		$maybe_purge_feed_url = get_author_feed_link( $user_id );

		if ( false !== $maybe_purge_feed_url ) {
			$user_purge_urls[] = $maybe_purge_feed_url;
		}

		/**
		 * Filter array of URLs to purge when a user action is triggered.
		 *
		 * @param array $user_purge_urls List of URLs to purge for this user.
		 */
		return apply_filters( 'wp_irving_cache_user_purge_urls', $user_purge_urls );
	}

	/**
	 * Fire purge requests for post purge urls.
	 *
	 * @param int\WP_Post $post Post object or ID.
	 */
	public function queue_post_purge_requests( $post ) {
		$purge_urls = $this->get_post_purge_urls( $post );
		$this->queue_bulk_purge_request( $purge_urls );
	}

	/**
	 * Fire purge requests for term purge urls.
	 *
	 * @param int\WP_Term $term Term object or ID.
	 */
	public function queue_term_purge_requests( $term ) {
		$purge_urls = $this->get_term_purge_urls( $term );
		$this->queue_bulk_purge_request( $purge_urls );
	}

	/**
	 * Fire purge requests for user purge urls.
	 *
	 * @param int\WP_User $user user object or ID.
	 */
	public function queue_user_purge_requests( $user ) {
		$purge_urls = $this->get_user_purge_urls( $user );
		$this->queue_bulk_purge_request( $purge_urls );
	}

	/**
	 * Fire purge requests for an array of permalinks.
	 *
	 * @param array $permalinks Permalinks.
	 */
	public function queue_bulk_purge_request( $permalinks = [] ) {
		self::$purge_queue = array_unique(
			array_map(
				function ( $url ) {
					return str_replace( home_url(), '', $url );
				},
				array_merge( self::$purge_queue, $permalinks )
			)
		);
	}

	/**
	 * Fire purge request.
	 */
	public function fire_purge_request() {
		// Do not fire purges while importing.
		if (
			( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) ||
			empty( self::$purge_queue )
		) {
			return;
		}

		// Fire the request to bust both VIP and Irving Redis cache.
		wp_remote_post(
			home_url( '/purge-cache' ),
			[
				'body'    => json_encode( [ 'paths' => self::$purge_queue ] ),
				'headers' => [
					'Content-Type' => 'application/json; charset=utf-8',
				],
			]
		);
		self::$purge_queue = [];
	}

	/**
	 * Fire wipe out request.
	 */
	public function fire_wipe_request() {
		wp_remote_post( home_url( '/purge-cache' ) );
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
					<?php esc_html_e( 'Purge Site Cache', 'wp-irving' ); ?>
				</h2>

				<p>
					<?php esc_html_e( 'Use with care. Purging the entire site cache will negatively impact performance for a short period of time.', 'wp-irving' ); ?>
				</p>

				<?php submit_button( __( 'Purge Cache', 'wp-irving' ) ); ?>
			</form>

			<hr>
		</div>
		<?php
	}
}

add_action(
	'init',
	function() {
		\WP_Irving\Cache::instance();
	}
);
