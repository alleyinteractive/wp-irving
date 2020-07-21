<?php
/**
 * Class file for the cache endpoint.
 *
 * @package WP_Irving
 */

namespace WP_Irving\REST_API;

/**
 * Cache Endpoint.
 */
class Cache_Endpoint extends Endpoint {
	/**
	 * Attach to required hooks for endpoint.
	*/
	public function __construct() {
		parent::__construct();

		// Register the route.
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		// Cache purge actions.
		add_action( 'wp_irving_site_cache_purge', [ $this, 'purge_site_cache' ] );
		add_action( 'wp_irving_page_cache_purge', [ $this, 'purge_page_cache' ] );
	}

	/**
	 * Register the REST API routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			self::get_namespace(),
			'/purge-cache',
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'get_route_response' ],
			]
		);
	}

	/**
	 * Callback for the route.
	 *
	 * @param  WP_REST_Request $request Request object.
	 */
	public function get_route_response( $request ) {
		// Get the request body.
		$request_params = $request->get_body_params();
		// Get the requested action.
		$action = $request_params['action'];

		// Ensure the action exists prior to firing.
		if ( !empty( $action ) ) {
			if ( $action === 'irving_site_cache_purge' ) {
				do_action( 'wp_' . $action );
			}

			if ( $action === 'irving_page_cache_purge' ) {
				// Get the route.
				$route = $request_params['route'];
				// Ensure the requested route is not empty prior
				// to firing the action.
				if ( !empty( $route ) ) {
					do_action( 'wp_' . $action, $route );
				}
			}
		}
	}

	/**
	 * Purge the site cache.
	 */
	public function purge_site_cache() {
		if ( function_exists( 'pantheon_wp_clear_edge_paths' ) ) {
			\WP_Irving\Pantheon::pantheon_flush_site();
		} else {
			\WP_Irving\Cache::instance()->fire_wipe_request();
		}
	}

	/**
	 * Purge the cache for a specific route/page.
	 *
	 * @param string $route The target route to purge from the cache.
	 */
	public function purge_page_cache( $route ) {
		// Check to see if the action is being executed on a VIP Go enabled
		// environment. If so, use VIP's purge function.
		if ( function_exists( 'wpcom_vip_purge_edge_cache_for_url' ) ) {
			wpcom_vip_purge_edge_cache_for_url( $route );
		}
		// Check for Pantheon environments.
		if ( function_exists( 'patheon_wp_clear_edge_paths' ) ) {
			pantheon_wp_clear_edge_paths( [$route] );
		}
	}
}
