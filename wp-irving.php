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

namespace WP_Irving;

define( 'WP_IRVING_PATH', dirname( __FILE__ ) );
define( 'WP_IRVING_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_IRVING_VERSION', '1.0' );

// Flush rewrite rules when the plugin is activated or deactivated.
register_activation_hook( __FILE__, 'flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

// Base classes.
require_once WP_IRVING_PATH . '/inc/endpoints/class-endpoint.php';

// API.
require_once WP_IRVING_PATH . '/inc/endpoints/class-components-endpoint.php';
require_once WP_IRVING_PATH . '/inc/endpoints/class-components-registry-endpoint.php';
require_once WP_IRVING_PATH . '/inc/endpoints/class-data-endpoint.php';
require_once WP_IRVING_PATH . '/inc/endpoints/class-form-endpoint.php';
require_once WP_IRVING_PATH . '/inc/endpoints/class-cache-endpoint.php';

// Integrations.
require_once WP_IRVING_PATH . '/inc/integrations/class-archiveless.php';
require_once WP_IRVING_PATH . '/inc/integrations/class-google-amp.php';
require_once WP_IRVING_PATH . '/inc/integrations/class-jwt-auth.php';
require_once WP_IRVING_PATH . '/inc/integrations/class-new-relic.php';
require_once WP_IRVING_PATH . '/inc/integrations/class-safe-redirect-manager.php';
require_once WP_IRVING_PATH . '/inc/integrations/class-vip-go.php';
require_once WP_IRVING_PATH . '/inc/integrations/class-pantheon.php';
require_once WP_IRVING_PATH . '/inc/integrations/class-wpcom-legacy-redirector.php';
require_once WP_IRVING_PATH . '/inc/integrations/class-yoast.php';

// Replicating WP Core functionality.
require_once WP_IRVING_PATH . '/inc/class-admin.php';
require_once WP_IRVING_PATH . '/inc/class-previews.php';
require_once WP_IRVING_PATH . '/inc/class-cache.php';

// Component registry.
require_once WP_IRVING_PATH . '/inc/components/class-context-store.php';
require_once WP_IRVING_PATH . '/inc/components/class-registry.php';
require_once WP_IRVING_PATH . '/inc/components/class-component.php';
require_once WP_IRVING_PATH . '/inc/components/namespace.php';

// Redirects.
require_once WP_IRVING_PATH . '/inc/redirects.php';

// Rewrite rules.
require_once WP_IRVING_PATH . '/inc/rewrites.php';

// Template loading.
require_once WP_IRVING_PATH . '/inc/templates/admin-bar.php';
require_once WP_IRVING_PATH . '/inc/templates/namespace.php';
require_once WP_IRVING_PATH . '/inc/templates/site-theme.php';

// Debugging helpers.
require_once WP_IRVING_PATH . '/inc/debug.php';

// Register endpoints.
new REST_API\Components_Endpoint();
new REST_API\Components_Registry_Endpoint();
new REST_API\Data_Endpoint();
new REST_API\Form_Endpoint();
new REST_API\Cache_Endpoint();

// Bootstrap functionality.
Components\bootstrap();
Templates\bootstrap();
