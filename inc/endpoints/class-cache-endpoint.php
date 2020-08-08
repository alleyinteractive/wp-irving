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
				'callback'            => [ $this, 'get_route_response' ],
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
	}

	/**
	 * Permissions check.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return bool|\WP_Error
	 */
	public function permissions_check( $request ) {

		/**
		 * Filter the permissions check.
		 *
		 * @param bool|\WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'wp_irving_cache_endpoint_permissions_check', current_user_can( 'manage_options' ), $request );
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
		if ( ! empty( $action ) ) {
			$response = [
				'success' => true,
				'message' => __( 'Cache purged successfully.', 'wp-irving' ),
			];

			if ( 'irving_site_cache_purge' === $action ) {
				do_action( 'wp_irving_site_cache_purge' );

				return new \WP_REST_Response( $response );
			}

			if ( 'irving_page_cache_purge' === $action ) {
				// Get the route.
				$route = $request_params['route'];
				// Ensure the requested route is not empty prior
				// to firing the action.
				if ( ! empty( $route ) ) {
					do_action( 'wp_irving_page_cache_purge', $route );

					return new \WP_REST_Response( $response );
				}
			}
		}

		$response = new \WP_Error( 500, __( 'There was an error clearing the cache. Please check the network tab in the developer console for more information.', 'wp-irving' ) );

		return $response;
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
			pantheon_wp_clear_edge_paths( [ $route ] );
		}
	}
}
