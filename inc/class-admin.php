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
	 * @param  array           $actions Action links.
	 * @param  \WP_Term|object $term    WP_Term object.
	 * @return array Updated action links.
	 */
	public function add_api_link_to_terms( array $actions, $term ) : array {

		// Get and validate term permalink.
		$permalink = get_term_link( $term );
		if ( $permalink instanceof \WP_Error ) {
			return $actions;
		}

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
}

( new \WP_Irving\Admin() )->instance();
