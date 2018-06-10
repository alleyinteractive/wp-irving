<?php
/**
 * Plugin Name:     WP Irving
 * Description:     Use WordPress as the API for Irving.
 * Author:          Alley
 * Author URI:      https://alley.co
 * Text Domain:     wp-irving
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         WP_Irving
 */

define( 'WP_IRVING_PATH', dirname( __FILE__ ) );
define( 'WP_IRVING_VERSION', '1.0' );

// Base classes.
require_once( WP_IRVING_PATH . '/inc/endpoints/class-endpoint.php' );
require_once( WP_IRVING_PATH . '/inc/components/class-component.php' );

// API.
require_once( WP_IRVING_PATH . '/inc/endpoints/class-components-endpoint.php' );

// Shapes.
require_once( WP_IRVING_PATH . '/inc/components/class-admin-bar.php' );
