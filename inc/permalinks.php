<?php
/**
 * Setup Alley React redirects and other url related helpers.
 */

namespace WP_Irving;

/**
 * Convert any URL to an app url.
 *
 * @param  string $url Old URL.
 * @return string      App URL.
 */
function convert_wp_url_to_app_url( $url ) {

	// Ensure we have an app url.
	if ( defined( 'ONBEING_APP_URL' ) && ONBEING_APP_URL ) {

		// Parse url.
		$url_parts = wp_parse_url( $url );
		if ( isset( $url_parts['path'] ) ) {
			$url = ONBEING_APP_URL . $url_parts['path'];
		} else {
			$url = ONBEING_APP_URL;
		}
	}
	return $url;
}

/**
 * Modify post links to use the app domain.
 *
 * @param  string  $permalink Old WP permalink.
 * @param  WP_Post $post      Post object.
 * @return string             New app permalink.
 */
function modify_permalinks( $permalink, $post ) {
	// Hardcode `/blog/` to post permalinks.
	if (
		$post instanceof \WP_Post &&
		'post' === $post->post_type &&
		(
			'publish' === $post->post_status ||
			'archiveless' === $post->post_status
		)
	) {
		$permalink = str_replace( home_url(), home_url( '/blog' ), $permalink );
	}
	return convert_wp_url_to_app_url( $permalink );
}
add_filter( 'post_link', __NAMESPACE__ . '\modify_permalinks', 10, 2 );
add_filter( 'page_link', __NAMESPACE__ . '\modify_permalinks', 10, 2 );
add_filter( 'post_type_link', __NAMESPACE__ . '\modify_permalinks', 10, 2 );

/**
 * Redirect all template calls to the head of the site.
 */
function redirect_template_calls() {
	$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
	if (
		! is_feed()
		&& 'feed' !== get_query_var( 'dispatch' )
		&& false === strpos( $request_uri, '.xml' )
		&& isset( $request_uri )
		&& defined( 'ONBEING_APP_URL' )
		&& ONBEING_APP_URL
	) {
		$app_url = convert_wp_url_to_app_url( $request_uri );
		wp_redirect( $app_url );
		exit();
	}
}
add_action( 'template_redirect', __NAMESPACE__ . '\redirect_template_calls' );

/**
 * Add API endpoint link to post types.
 *
 * @param  array   $actions Action links.
 * @param  WP_Post $post    WP_Post object.
 * @return array            Updated action links.
 */
function add_api_endpoint_permalink( $actions, $post ) {

	// Map post type to correct API location.
	$endpoint = $post->post_type;
	if ( 'post' === $endpoint ) {
		$endpoint = 'posts';
	} elseif ( 'page' === $endpoint ) {
		$endpoint = 'pages';
	}

	$actions['api'] = sprintf(
		'<a href="%1$s">API</a>',
		esc_url( rest_url( "wp/v2/{$endpoint}/{$post->ID}" ) )
	);

	return $actions;
}
add_filter( 'post_row_actions', __NAMESPACE__ . '\add_api_endpoint_permalink', 10, 2 );
add_filter( 'page_row_actions', __NAMESPACE__ . '\add_api_endpoint_permalink', 10, 2 );
