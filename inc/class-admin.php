<?php
/**
 * Modifications to the WP Admin for Irving.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Singleton class for customizing the admin.
 */
class Admin {

	/**
	 * Capability required to see API links.
	 *
	 * @var string
	 */
	public $api_link_cap;

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
	 * Setup the singleton. Validate JWT is installed, and setup hooks.
	 */
	public function setup() {
		add_action( 'init', [ $this, 'set_cap' ] );
		add_action( 'init', [ $this, 'hook_taxonomy_row_actions' ] );
		add_filter( 'post_row_actions', [ $this, 'add_api_link_to_posts' ], 10, 2 );
		add_filter( 'page_row_actions', [ $this, 'add_api_link_to_posts' ], 10, 2 );
		add_filter( 'admin_bar_menu', [ $this, 'add_api_link_to_admin_bar' ], 999 );
	}

	/**
	 * Set the capability for WP-Irving API links.
	 */
	public function set_cap() {
		/**
		 * Filter the capability required to view links to the WP-Irving API.
		 *
		 * @param string $capability. Defaults to `manage_options`.
		 */
		$this->api_link_cap = apply_filters( 'wp_irving_api_link_cap', 'manage_options' );
	}

	/**
	 * Add API endpoint link to post row actions.
	 *
	 * @param  array    $actions Action links.
	 * @param  \WP_Post $post    WP_Post object.
	 * @return array Updated action links.
	 */
	public function add_api_link_to_posts( array $actions, \WP_Post $post ) : array {

		// Only apply to published posts.
		if ( 'publish' !== $post->post_status ) {
			return $actions;
		}

		// Get post permalink.
		$permalink = get_permalink( $post );

		// Get the API URL, allowing it to be filtered.
		$path_url = \WP_Irving\REST_API\Components_Endpoint::get_wp_irving_api_url( $permalink );
		$path_url = apply_filters( 'wp_irving_post_row_action_path_url', $path_url, $post );

		// Add new link.
		if ( current_user_can( $this->api_link_cap ) ) {
			$actions['api'] = sprintf(
				'<a href="%1$s">API</a>',
				esc_url( $path_url )
			);
		}

		return $actions;
	}

	/**
	 * Adds the `add_api_link_to_terms` filter to all taxonomy row actions.
	 *
	 * This is a workaround for the `tag_row_actions` hook being deprecated in WP 5.4.
	 * See: https://core.trac.wordpress.org/ticket/49808.
	 *
	 * @return void
	 */
	public function hook_taxonomy_row_actions() {
		foreach ( get_taxonomies() as $taxonomy ) {
			add_filter( "${taxonomy}_row_actions", [ $this, 'add_api_link_to_terms' ], 10, 2 );
		}
	}

	/**
	 * Add API endpoint link to term row actions.
	 *
	 * @param  array    $actions Action links.
	 * @param  \WP_Term $term    WP_Term object.
	 * @return array Updated action links.
	 */
	public function add_api_link_to_terms( array $actions, \WP_Term $term ) : array {

		// Get term permalink.
		$permalink = get_term_link( $term );

		// Get the API URL, allowing it to be filtered.
		$path_url = \WP_Irving\REST_API\Components_Endpoint::get_wp_irving_api_url( $permalink );
		$path_url = apply_filters( 'wp_irving_term_row_action_path_url', $path_url, $term );

		// Add new link.
		if ( current_user_can( $this->api_link_cap ) ) {
			$actions['api'] = sprintf(
				'<a href="%1$s">API</a>',
				esc_url( $path_url )
			);
		}
		return $actions;
	}

	/**
	 * Add api link node to the admin bar from post edit screens.
	 *
	 * @param  \WP_Admin_Bar $admin_bar WP Admin Bar object.
	 */
	public function add_api_link_to_admin_bar( \WP_Admin_Bar $admin_bar ) {
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! current_user_can( $this->api_link_cap ) ) {
			return;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		// Get screen and check for a object base.
		$screen = get_current_screen();

		if ( 'dashboard' === ( $screen->base ?? '' ) ) {
			$path_url = add_query_arg(
				'path',
				'/',
				rest_url( 'irving/v1/components/' )
			);
		}

		if (
			'term' === ( $screen->base ?? '' )
			&& isset( $_GET['tag_ID'] )
			&& isset( $_GET['taxonomy'] )
		) {

			// Get and validate term ID.
			$term_id = absint( $_GET['tag_ID'] );
			if ( 0 === $term_id ) {
				return;
			}

			// Get term.
			$term = get_term_by( 'term_taxonomy_id', $term_id, wp_unslash( $_GET['taxonomy'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			// Get term permalink.
			$permalink = get_term_link( $term->term_id ?? 0 );

			// Get the API URL, allowing it to be filtered.
			$path_url = \WP_Irving\REST_API\Components_Endpoint::get_wp_irving_api_url( $permalink );
			$path_url = apply_filters( 'wp_irving_term_row_action_path_url', $path_url, $term );
		}

		if (
			'post' === ( $screen->base ?? '' )
			&& isset( $_GET['post'] )
		) {

			// Get and validate post ID.
			$post_id = absint( $_GET['post'] );
			if ( 0 === $post_id ) {
				return;
			}

			// Get post permalink.
			$permalink = get_the_permalink( $post_id );

			// Get the API URL, allowing it to be filtered.
			$path_url = \WP_Irving\REST_API\Components_Endpoint::get_wp_irving_api_url( $permalink );
			$path_url = apply_filters( 'wp_irving_post_row_action_path_url', $path_url, get_post( $post_id ) );
		}

		// Bail early.
		if ( empty( $path_url ) ) {
			return;
		}

		// Add node to admin bar.
		$admin_bar->add_node(
			[
				'id'    => 'wp_irving_api',
				'title' => __( 'WP-Irving API', 'wp-irving' ),
				'href'  => $path_url,
			]
		);
		//phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
}

( new \WP_Irving\Admin() )->instance();
