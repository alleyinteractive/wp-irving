<?php
/**
 * Previews.
 */

namespace WP_Irving;

/**
 * Generate a preview post link that works with Irving.
 *
 * @param  string  $url  Preview url.
 * @param  WP_Post $post Post object.
 * @return string        Modified preview url.
 */
function preview_revision_link( $url, $post ) {

	// Remove all query args
	$url = strtok( $url, '?' );

	// Setup an accurate path for drafts
	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( '/' === $path && 'draft' === $post->post_status ) {

		// Get a real url from the draft post
		$fake_post = clone $post;
		$fake_post->post_status = 'publish';
		$post_name = $fake_post->post_name ? $fake_post->post_name : $fake_post->post_title;
		$fake_post->post_name = sanitize_title( $post_name, $fake_post->ID );
		$url = get_permalink( $fake_post );
	}

	$url = convert_wp_url_to_app_url( $url );

	// Add preview
	$url = add_query_arg( array(
		'preview' => 'true',
		'preview_id' => $post->ID,
	), $url );

	return $url;
}
add_filter( 'preview_post_link', __NAMESPACE__ . '\preview_revision_link', 20, 2 );
add_filter( 'preview_page_link', __NAMESPACE__ . '\preview_revision_link', 20, 2 );

/**
 * Filter post row actions to modify preview url.
 *
 * @param  array   $actions Array of actions.
 * @param  WP_Post $post    Post object.
 * @return array            Updated array of actions.
 */
function post_row_actions( $actions, $post ) {
	if ( ! empty( $actions['view'] ) && 'publish' !== $post->post_status ) {
		$actions['view'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( preview_revision_link( get_permalink( $post ), $post ) ),
			esc_html__( 'Preview', 'onbeing' )
		);
	}
	return $actions;
}
add_filter( 'post_row_actions', __NAMESPACE__ . '\post_row_actions', 20, 2 );

/**
 * Handle meta data for revisions.
 *
 * @param array $keys Meta keys
 */
function add_meta_keys_to_revision( $keys ) {
	$keys[] = 'transcript';
	return $keys;
}
add_filter( 'wp_post_revision_meta_keys', __NAMESPACE__ . '\add_meta_keys_to_revision' );
