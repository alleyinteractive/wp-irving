<?php
/**
 * WP Irving integration for New Relic.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

class New_Relic {

	/**
	 * Constructor for class.
	 */
	public function __construct() {
		remove_action( 'rest_dispatch_request', 'wpcom_vip_rest_routes_for_newrelic' );
		add_filter( 'rest_dispatch_request', [ $this, 'rest_routes_for_newrelic' ], 10, 4 );
	}

	/**
	 * Add New Relic data for REST requests.
	 *
	 * This replaces and improves upon `wpcom_vip_rest_routes_for_newrelic` by:
	 *
	 *     1. Only naming transactions that are actual REST API requests (vs non-
	 *        REST API requests that use the REST API internally)
	 *     2. Unhooking itself so it only runs once
	 *
	 * @param mixed            $dispatch_result Dispatch result, will be used if not
	 *                                          empty.
	 * @param \WP_REST_Request $request         Request used to generate the
	 *                                          response.
	 * @param string           $route           Route matched for the request.
	 * @param array            $handler         Route handler used for the request.
	 * @return mixed Unaltered `$dispatch_result`.
	 */
	public function rest_routes_for_newrelic( $dispatch_result, $request, $route, $handler ) {
		if (
			extension_loaded( 'newrelic' )
			&& defined( 'REST_REQUEST' )
			&& true === REST_REQUEST
			&& function_exists( 'newrelic_add_custom_parameter' )
			&& function_exists( 'newrelic_name_transaction' )
			&& ! empty( $GLOBALS['wp']->query_vars['rest_route'] )
		) {
			$path = $GLOBALS['wp']->query_vars['rest_route'];
			if ( preg_match( '@^' . $route . '@i', $path ) ) {
				$name = preg_replace( '/\(\?P(<\w+?>).*?\)/', '$1', $route );

				\newrelic_name_transaction( $name );
				\newrelic_add_custom_parameter( 'wp-api', 'true' );
				\newrelic_add_custom_parameter( 'wp-api-route', $route );

				$params = $request->get_params();

				if ( ! empty( $params['context'] ) ) {
					\newrelic_add_custom_parameter( 'wp-api-context', $params['context'] );
				}

				if ( ! empty( $params['path'] ) ) {
					\newrelic_add_custom_parameter( 'wp-api-path', $params['path'] );
				}

				// Ensure this is only run once.
				\remove_filter( 'rest_dispatch_request', [ $this, 'rest_routes_for_newrelic' ] );
			}
		}

		return $dispatch_result;
	}
}

add_action(
	'init',
	function() {
		new \WP_Irving\New_Relic();
	}
);
