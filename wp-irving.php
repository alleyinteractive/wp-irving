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
define( 'WP_IRVING_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_IRVING_VERSION', '1.0' );

// Base classes.
require_once WP_IRVING_PATH . '/inc/endpoints/class-endpoint.php';
require_once WP_IRVING_PATH . '/inc/components/class-component.php';

// Traits.
require_once WP_IRVING_PATH . '/inc/traits/trait-content-item.php';
require_once WP_IRVING_PATH . '/inc/traits/trait-content-list.php';

// API.
require_once WP_IRVING_PATH . '/inc/endpoints/class-components-endpoint.php';

// Default components.
require_once WP_IRVING_PATH . '/inc/components/class-admin-bar.php';
require_once WP_IRVING_PATH . '/inc/components/class-byline.php';
require_once WP_IRVING_PATH . '/inc/components/class-content.php';
require_once WP_IRVING_PATH . '/inc/components/class-embed.php';
require_once WP_IRVING_PATH . '/inc/components/class-footer.php';
require_once WP_IRVING_PATH . '/inc/components/class-google-analytics.php';
require_once WP_IRVING_PATH . '/inc/components/class-head.php';
require_once WP_IRVING_PATH . '/inc/components/class-header.php';
require_once WP_IRVING_PATH . '/inc/components/class-html.php';
require_once WP_IRVING_PATH . '/inc/components/class-image.php';
require_once WP_IRVING_PATH . '/inc/components/class-load-more.php';
require_once WP_IRVING_PATH . '/inc/components/class-menu-item.php';
require_once WP_IRVING_PATH . '/inc/components/class-menu.php';
require_once WP_IRVING_PATH . '/inc/components/class-paginator.php';
require_once WP_IRVING_PATH . '/inc/components/class-term.php';

// Integrations.
require_once WP_IRVING_PATH . '/inc/integrations/class-safe-redirect-manager.php';

// CLI scripts.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once WP_IRVING_PATH . '/inc/cli/class-scaffold-cli-command.php';
}
