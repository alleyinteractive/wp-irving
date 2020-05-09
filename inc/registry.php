<?php
/**
 * Component registry helpers.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Get an instance of the registry class.
 *
 * @return \WP_Irving\Registry
 */
function get_registry(): Registry {
	static $registry;

	if ( empty( $registry ) ) {
		$registry = new Registry();
	}

	return $registry;
}

/**
 * Auto load some components which will then get registered. Defaults to the
 * theme directory and WP Irving plugin.
 */
function auto_register_components() {

	/**
	 * Filter the directories from which components should be autoloaded.
	 *
	 * @param array $directories Array of directories.
	 */
	$directories = apply_filters(
		'wp_irving_component_registry_directories',
		[
			'wp_irving' => WP_IRVING_PATH . '/inc/components/', // Load components from WP Irving.
			'theme'     => get_stylesheet_directory() . '/components/', // Load components from the activated theme.
		]
	);

	get_registry()->load_components( $directories );
}
add_filter( 'init', __NAMESPACE__ . '\auto_register_components' );
