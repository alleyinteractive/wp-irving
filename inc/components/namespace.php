<?php
/**
 * Component functionality.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Hook namespace functionality.
 */
function bootstrap() {
	add_filter( 'init', __NAMESPACE__ . '\auto_register_components' );
}
/**
 * Get an instance of the registry class.
 *
 * @return Registry
 */
function get_registry(): Registry {
	static $registry;

	if ( empty( $registry ) ) {
		$registry = new Registry();
	}

	return $registry;
}

/**
 * Returns the context object.
 *
 * Sets the default 'irving/post' context when first called.
 *
 * @return Context_Store The context store object.
 */
function get_context_store() {
	global $wp_query;
	static $context;

	if ( empty( $context ) ) {
		$context = new Context_Store();

		$context->set(
			[
				'irving/post_id'  => get_the_ID(),
				'irving/wp_query' => $wp_query,
			]
		);
	}

	return $context;
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
			'wp_irving' => WP_IRVING_PATH . '/inc/components/components', // Load components from WP Irving.
			'theme'     => get_stylesheet_directory() . '/components/', // Load components from the activated theme.
		]
	);

	get_registry()->load_components( $directories );
}

