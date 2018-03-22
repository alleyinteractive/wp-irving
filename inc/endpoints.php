<?php
/**
 * WP Irving endpoint entry point.
 */

namespace WP_Irving;

// Load endpoints
require_once WP_IRVING_PATH . '/inc/endpoints/landing-page.php';
require_once WP_IRVING_PATH . '/inc/endpoints/options.php';
require_once WP_IRVING_PATH . '/inc/endpoints/preview.php';

class Endpoints {

	// Enable endpoints
	public function __construct() {
		add_action( 'rest_api_init', __NAMESPACE__ . '\register_options_endpoint' );
		add_action( 'rest_api_init', __NAMESPACE__ . '\register_preview_endpoint' );
	}
}
