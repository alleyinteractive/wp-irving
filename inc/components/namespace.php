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
				'irving/term_id'  => ( $wp_query->get_queried_object() instanceof \WP_Term ) ? $wp_query->get_queried_object_id() : null,
			]
		);
	}

	return $wp_irving_context;
}s

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
			'wp_irving'     => WP_IRVING_PATH . '/inc/components/components', // Load components from WP Irving.
			'current-theme' => get_stylesheet_directory() . '/components/', // Load components from the activated theme.
			'parent-theme'  => get_template_directory() . '/components/', // Load components from any parent theme.
		]
	);

	get_registry()->load_components( $directories );
}

/**
 * Register a component.
 *
 * @param string $name Component name.
 * @param array  $args Component args.
 * @return bool Returns true if successful.
 */
function register_component( string $name, array $args = [] ): bool {
	return get_registry()->register_component( $name, $args );
}

/**
 * Unregister a component.
 *
 * @param string $name Component name.
 * @return bool Returns true if successful.
 */
function unregister_component( string $name ): bool {
	return get_registry()->unregister_component( $name );
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
 * Determine an `alt` attribute value for a given image.
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

/**
 * Convert any markup into an array of components.
 *
 * @param string $markup String of HTML.
 * @param array  $tags   Which HTML tags should be parsed.
 * @return array Array of parsed html tags.
 */
function html_to_components( string $markup, array $tags ): array {

	// phpcs:disable WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

	// Nothing found.
	if ( empty( $markup ) ) {
		return [];
	}

	// Create a DOMDocument and parse it.
	$dom = new \DOMDocument();
	@$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $markup ); // phpcs:ignore

	$components = [];

	// Loop though various tags and breakdown the markup into an array for
	// easy use elsewhere.
	foreach ( $tags as $tag ) {

		// Get all nodes for a given tag.
		$nodes = $dom->getElementsByTagName( $tag );
		foreach ( $nodes as $node ) {

			$component_args = [
				'config'   => [],
				'children' => [],
			];

			// Build attributes array.
			foreach ( $node->attributes as $attribute ) {
				$component_args['config'][ $attribute->localName ] = $attribute->nodeValue;
			}

			$children_text_node = $node->nodeValue ?? '';
			if ( ! empty( $children_text_node ) ) {
				$component_args['children'][] = $children_text_node;
			}

			$components[] = new Component( $tag, $component_args );
		}
	}

	// phpcs:enable WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

	return $components;
}

/**
 * Helper to get image attributes for a component.
 *
 * @param int    $attachment_id An attachment ID.
 * @param string $size          Optional. The intermediate image size to use. Default 'full'.
 * @return array An array of image configuration attributes.
 */
function get_image_component_attributes( int $attachment_id, string $size = 'full' ): array {

	$atts = wp_get_attachment_image_src( $attachment_id, $size );

	// Bail early if this isn't an attachment.
	if ( ! $atts ) {
		return [];
	}

	return [
		'src'    => $atts[0],
		'width'  => $atts[1],
		'height' => $atts[2],
		'alt'    => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		'sizes'  => wp_get_attachment_image_sizes( $attachment_id, $size ),
		'srcset' => wp_get_attachment_image_srcset( $attachment_id, $size ),
	];
}
