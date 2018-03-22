<?php
/**
 * WP Irving helpers.
 */

namespace WP_Irving;

/**
 * Convert a WP_Query or array of post_ids into an API appropriate response.
 *
 * @param  WP_Query|array  $posts   WP_Query or array of post_ids.
 * @param  WP_REST_Request $request WP_REST_Request object.
 * @return array                    WP-JSON formatted array
 */
function get_wp_json_posts( $posts, $request = null ) {

	// Loop through posts and turn them into rest api compliant structures.
	$response_posts = array();
	if ( ! $request instanceof \WP_REST_Request ) {
		$request = new \WP_REST_Request();
	}

	// Is $posts a WP_Query?
	if ( $posts instanceof \WP_Query ) {

		// No posts in query response.
		if ( ! $posts->have_posts() ) {
			return array();
		}

		// Reassign to actual posts array.
		$posts = $posts->posts;
	}

	// Loop through posts.
	foreach ( $posts as $post ) {
		// If $post isn't a WP_Post, assume it's a post_id.
		if ( ! $post instanceof \WP_Post ) {
			$post = get_post( absint( $post ) );

			// Check that $post was actually a valid post_id.
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
		}

		// Get PostController for Post Type.
		$posts_controller = new \WP_REST_Posts_Controller( $post->post_type );

		// Check permissions.
		if ( ! $posts_controller->check_read_permission( $post ) ) {
			continue;
		}

		// Add to response array.
		$data = $posts_controller->prepare_item_for_response( get_post( $post ), $request );
		$response_posts[] = $posts_controller->prepare_response_for_collection( $data );
	}

	return $response_posts;
}
