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

        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
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
	 *
	 * @return array
	 */
    public function get_route_response( $request ) {
        return $request;
    }
}