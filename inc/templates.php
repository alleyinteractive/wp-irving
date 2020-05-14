<?php
/**
 * Templates.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

use WP_Irving;
use WP_Query;

// Bootstrap filters.
add_filter( 'wp_irving_components_route', __NAMESPACE__ . '\\load_template', 10, 3 );

/**
 * Shallow template loader using core's template hierarchy.
 *
 * Based on wp-includes/template-loader.php.
 *
 * @param array    $data    Data object to be hydrated by templates.
 * @param WP_Query $query   The current WP_Query object.
 * @param string   $context The context for this request.
 * @return array A hydrated data object.
 */
function load_template( array $data, WP_Query $query, string $context ): array {
	$template = get_template_path( $query );

	if ( $template ) {
		$data = array_merge( $data, prepare_data_from_template( $template ) );
	}

	// Include defaults from a template if this is a server render.
	if ( 'site' === $context ) {
		$defaults = locate_template( [ 'defaults' ] );

		if ( $defaults ) {
			$data = array_merge( $data, prepare_data_from_template( $defaults, 'defaults' ) );
		}

		$data['defaults'] = traverse_components( $data['defaults'] );
	}

	$data['page'] = traverse_components( $data['page'] );

	return $data;
}

/**
 * Get the path to a template for a WP_Query object.
 *
 * @param WP_Query $query The current WP_Query object.
 * @return string A path to the template to load.
 */
function get_template_path( WP_Query $query ): string {
	// Filter the template hierarchy before processing.
	filter_template_loader();

	$tag_templates = array(
		'is_embed'             => 'get_embed_template',
		'is_404'               => 'get_404_template',
		'is_search'            => 'get_search_template',
		'is_front_page'        => 'get_front_page_template',
		'is_home'              => 'get_home_template',
		'is_privacy_policy'    => 'get_privacy_policy_template',
		'is_post_type_archive' => 'get_post_type_archive_template',
		'is_tax'               => 'get_taxonomy_template',
		'is_attachment'        => 'get_attachment_template',
		'is_single'            => 'get_single_template',
		'is_page'              => 'get_page_template',
		'is_singular'          => 'get_singular_template',
		'is_category'          => 'get_category_template',
		'is_tag'               => 'get_tag_template',
		'is_author'            => 'get_author_template',
		'is_date'              => 'get_date_template',
		'is_archive'           => 'get_archive_template',
	);

	$template = false;

	// Loop through each of the template conditionals, and find the appropriate template file.
	foreach ( $tag_templates as $tag => $template_getter ) {
		if ( call_user_func( [ $query, $tag ] ) ) {
			$template = call_user_func( $template_getter );
		}

		// Unsure if this is needed in JSON context.
		if ( $template ) {
			if ( 'is_attachment' === $tag ) {
				remove_filter( 'the_content', 'prepend_attachment' );
			}

			break;
		}
	}

	if ( ! $template ) {
		$template = get_index_template();
	}

	/**
	 * Filters the path of the current template.
	 *
	 * @param string   $template The path of the template to include.
	 * @param WP_Query $query    The current WP_Query object.
	 */
	return apply_filters( 'wp_irving_template_include', $template, $query );
}

/**
 * Prepares data for an Irving REST response from a template.
 *
 * @param string $template Full path to template.
 * @param string $type     Optional. The type of data being loaded from a template.
 *                         Defaults to 'page'.
 * @return array An associative array prepared for an Irving REST Response.
 */
function prepare_data_from_template( string $template, string $type = 'page' ): array {
	$components = [];

	ob_start();
	include $template;
	$contents = ob_get_clean();

	// Attempt to json decode it.
	$components = json_decode( $contents, true );

	// Validate success.
	if ( ! json_last_error() ) {
		return $components;
	}

	if ( has_blocks( $contents ) ) {
		$components[ $type ] = convert_blocks_to_components( parse_blocks( $contents ) );

		return $components;
	}

	$components['page'] = [
		[
			'name'   => 'templates/error',
			'config' => [ 'json_error' => json_last_error() ],
		],
	];

	return $components;
}

/**
 * Filter the WordPress template locating algorithm.
 *
 * The locate_template function in WordPress checks for hard coded template paths
 * which aren't filterable, so this implements a workaround to skip the file_exists checks
 * and return the template files for our own implementation of a locate_template function.
 *
 * See: https://core.trac.wordpress.org/ticket/48175
 *
 * @return void
 */
function filter_template_loader() {
	// All supported template types in WP Core.
	$template_types = [
		'index',
		'404',
		'archive',
		'author',
		'category',
		'tag',
		'taxonomy',
		'date',
		'embed',
		'home',
		'frontpage',
		'privacypolicy',
		'page',
		'paged',
		'search',
		'single',
		'singular',
		'attachment',
	];

	// Return an empty array in {$type}_template_hierarchy to avoid file lookups but use
	// the located templates to filter {$type}_template with our custom location.
	foreach ( $template_types as $type ) {
		add_filter( "{$type}_template_hierarchy", function ( $templates ) use ( $type ) {
			add_filter( "{$type}_template", function () use ( $templates ) {
				return locate_template( $templates );
			} );

			return [];
		} );
	}
}

/**
 * Return the full path to a template file.
 *
 * @param array $templates A list of possible template files to load.
 * @return string The path to the found template.
 */
function locate_template( array $templates ): string {
	$template_path = STYLESHEETPATH . '/templates/';

	/**
	 * Filter the path to Irving templates.
	 *
	 * @param string $template_path The full path to the template folder.
	 * @param array  $templates     A list of template files to locate.
	 */
	$template_path = apply_filters( 'wp_irving_template_path', $template_path, $templates );

	$located = '';

	foreach ( $templates as $template ) {
		// Normalize the template name without extension.
		$template_base = wp_basename( $template, '.php' );

		// Look for .php, .json, and then .html templates.
		$filetypes = [
			'php',
			'json',
			'html',
		];

		foreach ( $filetypes as $type ) {
			// Ensure filtered template paths are slashed.
			$path = trailingslashit( $template_path ) . $template_base . '.' . $type;

			// If the file is located, break out of filetype loop.
			if ( file_exists( $path ) ) {
				$located = $path;
				break;
			}
		}

		// Break out of template type loop.
		if ( $located ) {
			break;
		}
	}

	return $located;
}

/**
 * Return the full path to a template part file.
 *
 * @param string $template Relative path and/or name of the template part.
 * @return string The path to the found template part.
 */
function locate_template_part( string $template ): string {

	$template_part_path = STYLESHEETPATH . '/template-parts/';

	/**
	 * Filter the path to Irving template partss.
	 *
	 * @param string $template_part_path The full path to the template folder.
	 * @param string  $template           A list of template files to locate.
	 */
	$template_part_path = apply_filters( 'wp_irving_template_part_path', $template_part_path, $template );

	// Normalize the template name with[out extension.
	$template_base = wp_basename( $template, '.php' );

	// Look for .php, .json, and then .html templates.
	$filetypes = [
		'php',
		'json',
		'html',
	];

	foreach ( $filetypes as $type ) {
		// Ensure filtered template paths are slashed.
		$path = trailingslashit( $template_part_path ) . $template_base . '.' . $type;

		// If the file is located, break out of filetype loop.
		if ( file_exists( $path ) ) {
			return $path;
		}
	}

	return '';
}

/**
 * Convert an array of blocks into Irving components.
 *
 * @param array $blocks Array of blocks. Likely from parse_blocks.
 * @return array Irving component data.
 */
function convert_blocks_to_components( array $blocks ): array {
	$components = [];

	foreach ( $blocks as $block ) {
		if ( ! isset( $block['blockName'] ) ) {
			continue;
		}

		$block_config = [];

		// Add `content` from innerHTML.
		if ( ! empty( trim( $block['innerHTML'] ) ) ) {
			$block_config['content'] = trim( $block['innerHTML'] );
		}

		// Handle blocks that have a server render callback.
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
		if ( function_exists( $block_type->render_callback ?? '' ) && ! isset( $block_config['content'] ) ) {
			$block_config['content'] = call_user_func( $block_type->render_callback );
		}

		$components[] = [
			'name'     => $block['blockName'],
			'config'   => array_merge( $block['attrs'], $block_config ),
			'children' => convert_blocks_to_components( $block['innerBlocks'] ),
		];
	}

	return $components;
}

/**
 * Recursively iterate on a component tree.
 *
 * @param array $components Array of components.
 * @return array
 */
function traverse_components( array $components ): array {

	foreach ( $components as $index => &$component ) {

		// This allows us to set text nodes.
		if ( is_string( $component ) ) {
			continue;
		}

		// Ensure we have all the right field and types.
		$component = ensure_component_fields_exist( $component );

		$component = handle_template_parts( $component );
		$component = handle_data_provider( $component );
		$component = handle_component_config_callbacks( $component );
		$component = handle_component_callbacks( $component );

		// Recursively loop though.
		if ( ! empty( $component['children'] ) ) {
			$component['children'] = traverse_components( $component['children'] );
		}

		// Ensure config and data providers are actually objects.
		$component = validate_final_component( $component );
	}

	return $components;
}


/**
 * Validate and typecast a component.
 *
 * @param array $component Component.
 * @return array
 */
function ensure_component_fields_exist( $component ) {

	if ( is_string( $component ) ) {
		return $component;
	}

	return [
		'name'          => (string) ( $component['name'] ?? '' ),
		'config'        => (array) ( $component['config'] ?? [] ),
		'data_provider' => (array) ( $component['data_provider'] ?? [] ),
		'children'      => (array) ( $component['children'] ?? [] ),
	];
}

/**
 * Modify the final output of a component.
 *
 * @param array $component Component.
 * @return array
 */
function validate_final_component( $component ) {

	if ( is_string( $component ) ) {
		return $component;
	}

	$component                  = ensure_component_fields_exist( $component );
	$component['config']        = (object) $component['config'];
	$component['data_provider'] = (object) $component['data_provider'];
	$component['children']      = array_values( array_filter( (array) ( $component['children'] ?? [] ) ) );

	unset( $component['data_provider'] );

	return $component;
}


/**
 * Pull in template parts.
 *
 * @param array $component Component.
 * @return array
 */
function handle_template_parts( $component ) {

	// Check if this is a template part.
	$name = $component['name'] ?? '';
	if ( 0 !== strpos( $name, 'template-parts/' ) ) {
		return $component;
	}

	$template_part_name = str_replace( 'template-parts/', '', $component['name'] );

	$template = locate_template_part( $template_part_name );

	$template_data = prepare_data_from_template( $template );

	if ( isset( $template_data['name'] ) ) {
		$template_data = [ $template_data ];
	}

	$component['name'] = 'irving/passthrough';
	$component['children'] = $template_data;

	return $component;
}

/**
 * Pass data provider values down to children components.
 *
 * @param array $component Component.
 * @return array
 */
function handle_data_provider( $component ) {

	// If there's no data provider, or children, don't do anything.
	if ( empty( $component['data_provider'] ) || empty( $component['children'] ) ) {
		return $component;
	}

	foreach ( $component['children'] as &$child_component ) {

		if ( is_string( $child_component ) ) {
			continue;
		}

		$child_component = ensure_component_fields_exist( $child_component );

		$child_component['data_provider'] = array_merge_recursive(
			$child_component['data_provider'],
			$component['data_provider']
		);
	}

	return $component;
}

/**
 * Loop through component config values. If we match a {{component/name}} then
 * we execute the callback, and return the value to the config.
 *
 * @todo Determine if this functionality can be scrapped in favor of a data
 *       provider. This is proof of concept purely for discussion and
 *       consideration.
 *
 * @example
 * {
 *   "name": "example",
 *   "config": {
 *     "href": "{{post/permalink}}"
 *   }
 * }
 *
 * @param array $component Component.
 * @return array
 */
function handle_component_config_callbacks( $component ): array {

	// Ensure component config exists.
	if ( ! isset( $component['config'] ) || empty( $component['config'] ) ) {
		return $component;
	}

	foreach ( $component['config'] as $key => $value ) {

		if ( ! is_string( $value ) ) {
			continue;
		}

		// For each key, check if the value has a handlebars syntax.
		preg_match_all( '/{{(.+)}}/', $value, $matches );
		$matches = array_filter( $matches );

		// If we found a result, execute the component callback, and assign to
		// the key.
		if ( ! empty( $matches ) ) {
			$component_name = $matches[1][0];
			$component['config'][ $key ] = handle_component_callbacks(
				[
					'name'          => $component_name,
					'data_provider' => $component['data_provider'],
				]
			);
		}
	}

	return $component;
}

/**
 * Fire the callback on registered components.
 *
 * @param array $component Component.
 * @return array
 */
function handle_component_callbacks( array $component ) {

	// Check the component registry.
	$registered_component = WP_Irving\get_registry()->get_registered_component( $component['name'] );
	if ( is_null( $registered_component ) || ! is_callable( $registered_component['callback'] ?? '' ) ) {
		return $component;
	}

	// Execute callback.
	return call_user_func_array( $registered_component['callback'], [ $component ] );
}

/**
 * Returns the template context object.
 *
 * Sets the default 'irving/post' context when first called.
 *
 * @return WP_Irving\Context_Store The context store object.
 */
function get_template_context() {
	static $context;

	if ( empty( $context ) ) {
		$context = new WP_Irving\Context_Store();

		// Set default context.
		$context->set( 'irving/post', get_the_ID() );
	}

	return $context;
}
