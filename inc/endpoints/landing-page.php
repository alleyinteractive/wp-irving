<?php
/**
 * Endpoint to help get the correct landing page.
 */

namespace WP_Irving\Endpoint;

/**
 * Add custom endpoint to get a landing page.
 */
function register_landing_page_endpoint() {
	register_rest_route(
		'babbel/v1',
		'/landing-page/(?P<landing_page>\w+)/',
		array(
			'methods'  => \WP_REST_Server::READABLE,
			'callback' => function( $request ) {

				// Get landing page type.
				$landing_page_type = $request['landing_page'];

				if ( empty( $posts ) ) {
					return rest_ensure_response( array(
						'code' => 'rest_landing_page_invalid_key',
						'message' => 'Invalid landing page key',
						'data' => array(
							'status' => 204,
						),
					), 204 );
				}

				// Start building response.
				$response = rest_ensure_response( $posts );

				// Add headers for pagination.
				if (
					! empty( $posts[0][ $landing_page_type ]['total'] )
					&& ! empty( $posts[0][ $landing_page_type ]['total_pages'] )
				) {
					$response->header( 'X-WP-Total', absint( $posts[0][ $landing_page_type ]['total'] ) );
					$response->header( 'X-WP-TotalPages', absint( $posts[0][ $landing_page_type ]['total_pages'] ) );
				}

				if ( is_wp_error( $response ) ) {
					wp_die( esc_html( $response->get_error_message() ) );
				}

				return $response;
			},
		)
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\register_landing_page_endpoint' );
