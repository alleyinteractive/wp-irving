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
 * Sets a default context when first called.
 *
 * @return Context_Store The context store object.
 */
function get_context_store(): Context_Store {
	global $wp_query;
	global $wp_irving_context;

	if ( empty( $wp_irving_context ) ) {
		$wp_irving_context = new Context_Store();

		$wp_irving_context->set(
			[
				'irving/post_id'  => get_the_ID(),
				'irving/wp_query' => $wp_query,
			]
		);
	}

	return $wp_irving_context;
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

/**
 * Register a component.
 *
 * @param string $name Component name.
 * @param array  $args Component args.
 */
function register_component( string $name, array $args = [] ) {
	return get_registry()->register_component( $name, $args );
}

/**
 * Register a component using a json config.
 *
 * @param string $config_path JSON config file.
 * @param array  $args        Component args.
 * @return bool Returns true if successful.
 */
function register_component_from_config( string $config_path, array $args = [] ): bool {

	// Add the extension if necessary.
	if ( false === strpos( $config_path, '.json' ) ) {
		$config_path .= '.json';
	}

	// Validate the config file exists.
	if ( ! file_exists( $config_path ) ) {
		wp_die(
			sprintf(
				// Translators: %1$s: Error message, %2$s Template path.
				esc_html__( 'Error: Could not find component.json at %1$s. Double check the filename.', 'wp-irving' ),
				esc_html( $config_path )
			)
		);
		return false;
	}

	// Load and decode JSON component config.
	// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
	$config = file_get_contents( $config_path );
	$config = json_decode( $config, true );

	// Validate config loaded and `name` is available.
	if ( is_null( $config ) || ! isset( $config['name'] ) ) {
		wp_die(
			sprintf(
				// Translators: %1$s: Error message, %2$s Template path.
				esc_html__( 'Error: %1$s found in %2$s.', 'wp-irving' ),
				esc_html( json_last_error_msg() ),
				esc_html( $config_path )
			)
		);
		return false;
	}

	return register_component(
		$config['name'],
		array_merge_recursive( $config, $args )
	);
}

/**
 * Determine an `alt` attribute value for this image.
 *
 * @param int $attachment_id Attachment ID.
 * @return string The alt attribute.
 */
function get_image_alt( int $attachment_id ): string {

	// Get the alt.
	$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

	// Fallback to caption.
	if ( empty( $alt ) ) {
		$alt = wp_get_attachment_caption( get_post_thumbnail_id( $attachment_id ) );
	}

	// phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter
	return trim( strip_tags( $alt ) );
}
