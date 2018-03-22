<?php
/**
 * Endpoint to get a preview or draft.
 */

namespace WP_Irving\Endpoint;

/**
 * Add custom endpoint to get preview.
 */
function register_preview_endpoint() {
	register_rest_route(
		'wp-irving/v1',
		'/preview/(?P<post_id>\d+)/',
		array(
			'methods'  => \WP_REST_Server::READABLE,
			'permission_callback' => function() {
				// Restrict endpoint to only users who have the edit_posts capability.
				if ( ! current_user_can( 'edit_posts' ) ) {
					return new \WP_Error(
						'rest_no_route',
						esc_html__( 'You do not have permission to use this route.', 'onbeing' ),
						array( 'status' => 404 )
					);
				}
				return true;
			},
			'callback' => function( $request ) {

				// Get and validate post
				$post = get_post( $request['post_id'] );
				if ( ! $post instanceof \WP_Post ) {
					return rest_ensure_response( array(
						'code' => 'rest_invalid_post_id',
						'message' => __( 'No post found', 'onbeing' ),
						'data' => array(
							'status' => 404,
						),
					) );
				}

				// Build base response
				$posts_controller = new \WP_REST_Posts_Controller( $post->post_type );

				// Add to response array
				$data = $posts_controller->prepare_item_for_response( $post, $request );
				$response_post = $posts_controller->prepare_response_for_collection( $data );

				switch ( $post->post_status ) {
					case 'draft':
						$response_post['slug'] = sanitize_title( $post->post_title );
						break;
					case 'publish':
						// Check for revisions
						$revision = wp_get_post_revision( $post->ID );

						// For posts that don't support revisions
						if ( empty( $revision ) ) {
							$revision_args = array(
								'order'            => 'DESC',
								'orderby'          => 'date',
								'post_parent'      => $post->ID,
								'post_status'      => 'inherit',
								'post_type'        => 'revision',
								'posts_per_page'   => 1,
								'suppress_filters' => false,
							);
							$revisions = get_posts( $revision_args );
							$revision = $revisions[0] ?? $revision;
						}

						if ( $revision instanceof \WP_Post ) {
							$response_post['title']['rendered'] = $revision->post_title;
							$response_post['content']['rendered'] = apply_filters( 'the_content', $revision->post_content );
							$response_post['excerpt']['rendered'] = $revision->post_excerpt;
						}
						break;
				}

				$response = rest_ensure_response( array( $response_post ) );
				$response->header( 'X-WP-Total', 1 );
				$response->header( 'X-WP-TotalPages', 1 );
				return $response;
			}
		)
	);
}
