<?php
/**
 * Entry point for the plugin.
 *
 * Plugin Name: WP Irving
 * Description: The WordPress side of Irving - Alley's React Ecosystem
 * Author: Alley Interactive, jameswburke
 * Version: 0.1
 * Author URI: http://alleyinteractive.com
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Current version of WP_Irving.
 */
define( 'ALLEYPACK_VERSION', '0.1.0' );

/**
 * Filesystem path to WP_Irving.
 */
define( 'WP_IRVING_PATH', dirname( __FILE__ ) );

/**
 * Require Shapes.
 */
require_once WP_IRVING_PATH . '/inc/cors.php';
require_once WP_IRVING_PATH . '/inc/endpoints.php';
require_once WP_IRVING_PATH . '/inc/helpers.php';
require_once WP_IRVING_PATH . '/inc/permalinks.php';
require_once WP_IRVING_PATH . '/inc/previews.php';
