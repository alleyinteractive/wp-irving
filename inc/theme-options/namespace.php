<?php
/**
 * Theme option functionality.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Theme_Options;

/**
 * Hook namespace functionality.
 */
function bootstrap() {
	add_filter( 'init', __NAMESPACE__ . '\auto_register_components' );
}

function add_theme_option( array $args ) {
	$args = wp_parse_args(
		$args,
		[
			'callback' => '',
			'default'  => '',
			'key'      => '',
			'label'    => '',
			'location' => '',
			'option'   => '',
			'type'     => '',
		]
	);
}
