<?php
/**
 * Integrations functionality.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

/**
 * Hook namespace functionality.
 */
function bootstrap() {
	add_filter( 'init', __NAMESPACE__ . '\load_integrations_manager' );
	add_filter( 'plugins_loaded', __NAMESPACE__ . '\auto_register_plugin_integrations' );
}

/**
 * Load the integrations manager and register the base integrations.
 */
function load_integrations_manager() {
	// Instantiate the integrations manager.
	$manager = new \WP_Irving\Integrations\Integrations_Manager();
	$manager->setup();

	// Register integrations.
	auto_register_integrations();
}

/**
 * Auto load the integrations.
 */
function auto_register_integrations() {
	$integrations = [
		'archiveless'             => __NAMESPACE__ . '\Archiveless',
		'coral'                   => __NAMESPACE__ . '\Coral',
		'google_analytics'        => __NAMESPACE__ . '\Google_Analytics',
		'google_amp'              => __NAMESPACE__ . '\Google_AMP',
		'new_relic'               => __NAMESPACE__ . '\New_Relic',
		'pantheon'                => __NAMESPACE__ . '\Pantheon',
		'safe_redirect_manager'   => __NAMESPACE__ . '\Safe_Redirect_Manager',
		'vip_go'                  => __NAMESPACE__ . '\VIP_Go',
		'wpcom_legacy_redirector' => __NAMESPACE__ . '\WPCOM_Legacy_Redirector',
		'yoast'                   => __NAMESPACE__ . '\Yoast',
	];

	// Allow new integrations to be added via a filter.
	$integrations = apply_filters( 'wp_irving_active_integrations', $integrations );

	load_integrations( $integrations );
}

/**
 * Auto load the plugin-based integrations.
 */
function auto_register_plugin_integrations() {
	$integrations = [
		'jwt_auth' => __NAMESPACE__ . '\JWT_Auth',
		'pico'     => __NAMESPACE__ . '\Pico',
	];

	// Allow new integrations to be added via a filter.
	$integrations = apply_filters( 'wp_irving_active_plugin_integrations', $integrations );

	load_integrations( $integrations );
}

/**
 * Load the integrations provided by the autoload functions
 * or via the active integrations filters.
 *
 * @param array $integrations The integrations to instantiate.
 */
function load_integrations( array $integrations ) {
	foreach ( $integrations as $type => $class ) {

		// Ensure the integration exists and has an instance method.
		if ( is_callable( [ $class, 'instance' ] ) ) {
			// Create a singleton instance of this integration.
			$integration = call_user_func( [ $class, 'instance' ] );
		}
	}
}
