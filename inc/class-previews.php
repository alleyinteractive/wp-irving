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
		if ( apply_filters( 'wp_irving_enable_public_previews', false ) ) {
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

				// Hook into Irving's components request to check if it's for a preview.
				add_action( 'wp_irving_components_request', [ $this, 'check_for_previews' ] );
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
	 * Check if the request is for a preview/revision, and re-add WordPress
	 * Core's filter for handling such logic.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public function check_for_previews( $request ) {

		// Check if this is an authenticated request.
		if ( ! is_user_logged_in() ) {
			return;
		}

		$is_preview = wp_validate_boolean( $request->get_param( 'preview' ) ?? false );
		$preview_id = absint( $request->get_param( 'preview_id' ) ?? 0 );

		// Require `preview` to be true, and `preview_id` to be an integer.
		if ( false === $is_preview || 0 === $preview_id ) {
			return;
		}

		// Re-add the Core filter we removed when removing `_show_post_preview`.
		add_filter( 'the_preview', '_set_preview' );
	}
}

( new \WP_Irving\Previews() )->instance();
