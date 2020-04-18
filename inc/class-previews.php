<?php
/**
 * Replicating core WP previews for Irving.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Singleton class for customizing core's preview logic as needed to work with
 * Irving.
 */
class Previews {

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

		// Allow-list some query vars.
		add_filter( 'query_vars', [ $this, 'modify_query_vars' ] );

		// Remove the wp_die() that occurs on preview urls. We need to do this
		// or the SSR endpoint will fail in a bad way. This alone does not
		// expose any draft or revision data.
		remove_action( 'init', '_show_post_preview' );

		// Filter to enable/disable public previews.
		if ( apply_filters( 'wp_irving_enable_public_previews', true ) ) {
			add_action( 'wp_irving_components_request', [ $this, 'enabled_public_previews' ] );
		}

		// After plugins have loaded, check for JWT and modify the preview logic as needed.
		add_action(
			'plugins_loaded',
			function() {

				// Validate that JWT exists and is enabled.
				if ( ! defined( 'JWT_AUTH_VERSION' ) ) {
					return;
				}

				// Hook into Irving's query string to WP_Query process to modify some
				// logic for previews.
				add_action( 'wp_irving_components_wp_query', [ $this, 'modify_wp_query_for_previews' ], 10, 4 );
			}
		);
	}

	/**
	 * Allow-list some query vars for previews.
	 *
	 * @param  array $vars Allow-listed query vars.
	 * @return array
	 */
	public function modify_query_vars( array $vars ): array {
		$vars[] = 'preview_id';
		$vars[] = 'preview_nonce';
		return $vars;
	}

	/**
	 * Hook into the component API request and change the draft preview to be
	 * `publish`, mimicking a public post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public function enabled_public_previews( \WP_REST_Request $request ) {

		// Require `preview` to be true, and `p` to be an integer.
		if (
			false === wp_validate_boolean( $request->get_param( 'preview' ) )
			|| 0 === absint( $request->get_param( 'p' ) )
		) {
			return;
		}

		add_filter(
			'posts_results',
			function ( $posts ) {

				if ( empty( $posts ) ) {
					return $posts;
				}

				$posts[0]->post_status = 'publish';
				return $posts;
			}
		);
	}

	/**
	 * Modify the \WP_Query object that Irving creates to swap out the published post, for the correct revision.
	 *
	 * @param \WP_Query $wp_query      WP_Query object corresponding to this
	 *                                 request.
	 * @param string    $path          Request path.
	 * @param string    $custom_params Custom params.
	 * @param string    $params        Request params.
	 * @return \WP_Qury $wp_query WP_Query object for the request.
	 */
	public function modify_wp_query_for_previews( $wp_query, $path, $custom_params, $params ) {

		// Auth and a real route required.
		if ( ! is_user_logged_in() || ! $wp_query->have_posts() ) {
			return $wp_query;
		}

		$is_preview = wp_validate_boolean( $params['preview'] ?? false );
		$preview_id = absint( $params['preview_id'] ?? 0 );

		// Require `preview` to be true, and `p` to be an integer.
		if ( false === $is_preview || 0 === $preview_id ) {
			return $wp_query;
		}

		// Get all the revisions
		$revisions = wp_get_post_revisions( $wp_query->post->ID );

		// Use the preview_id, or fallback to the most recent revision.
		$revision = $revisions[ $preview_id ] ?? current( $revisions );

		$wp_query->posts[0] = $revision;
		$wp_query->post     = $revision;

		return $wp_query;
	}
}

( new \WP_Irving\Previews() )->instance();
