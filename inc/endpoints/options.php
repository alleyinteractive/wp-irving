<?php
/**
 * Endpoint to get site options.
 */

namespace WP_Irving\Endpoint;

/**
 * Add custom endpoint for options.
 */
function register_options_endpoint() {
	register_rest_route(
		'wp-irving/v1',
		'/options/',
		array(
			'methods'  => \WP_REST_Server::READABLE,
			'callback' => function( $request ) {

				// Build an array of options
				$options = array(
					'info'         => array(
						'title' => get_bloginfo( 'name' ),
					),
					'postTypes'    => apply_filters( 'alley_react_post_types', array_keys( get_post_types() ) ),
					'taxonomies'   => apply_filters( 'alley_react_taxonomies', array_keys( get_taxonomies() ) ),
					'menus'        => apply_filters( 'alley_react_menus', get_registered_nav_menus() ),
					'redirects'    => apply_filters( 'alley_react_redirects', array() ),
				);

				return apply_filters( 'alley_react_options', $options );
			}
		)
	);
}
