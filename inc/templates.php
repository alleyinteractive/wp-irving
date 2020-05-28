<?php
/**
 * Templates.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

use WP_Irving;
use WP_Query;
use WP_Irving\Component;

// Bootstrap filters.
add_filter( 'wp_irving_components_route', __NAMESPACE__ . '\\load_template', 10, 5 );

/**
 * Shallow template loader using core's template hierarchy.
 *
 * Based on wp-includes/template-loader.php.
 *
 * @param array            $data    Data object to be hydrated by templates.
 * @param \WP_Query        $query   The current WP_Query object.
 * @param string           $context The context for this request.
 * @param string           $path    The path for this request.
 * @param \WP_REST_Request $request WP_REST_Request object.
 * @return array A hydrated data object.
 */
function load_template(
	array $data,
	\WP_Query $query,
	string $context,
	string $path,
	\WP_REST_Request $request
): array {

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

		$data['defaults'] = hydrate_components( $data['defaults'] );
	}

	$data['page'] = hydrate_components( $data['page'] );

	// Automatically setup the <Helmet> tag.
	$data = setup_helmet( $data, $query, $context, $path, $request );

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

	// This is a .json template.
	if ( false !== strpos( $template, '.json' ) ) {

		// Attempt to json decode it.
		$components = json_decode( $contents, true );

		// Validate success.
		if ( ! json_last_error() ) {
			return $components;
		}

		wp_die(
			sprintf(
				// Translators: %1$s: Error message, %2$s Template path.
				esc_html__( 'Error: %1$s found in %2$s.', 'wp-irving' ),
				esc_html( json_last_error_msg() ),
				esc_html( $template )
			)
		);
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
 * @return string|false The path to the found template part. False if not.
 */
function locate_template_part( string $template ): string {

	$template_part_path = STYLESHEETPATH . '/template-parts/';

	/**
	 * Filter the path to Irving template parts.
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

	return false;
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
 * Hydrate components.
 *
 * @param array $components A list of components from a template.
 * @return array A hydrated array of components prepared for a REST response.
 */
function hydrate_components( array $components ) {

	$hydrated = [];

	foreach ( $components as $component ) {
		// Create Object Instance to hydrate initial config values.
		$component = setup_component( $component );

		// Bail early if this isn't a WP_Irving\Component.
		if ( ! $component instanceof Component ) {
			break;
		}

		$template_data = hydrate_template_parts( $component );

		// If the component is converted to template data,
		// add hydrated components to the array and move on.
		if ( ! empty( $template_data ) ) {
			array_push( $hydrated, ...$template_data );

			// A little cleanup.
			unset( $template_data );
			break;
		}

		// Set up config from context.
		$component->use_context( get_template_context() );

		// Run hydration callback.
		$component->do_callback();

		// Update context values.
		$component->provide_context( get_template_context() );

		// Recursively hydrate children.
		if ( ! empty( $component->get_children() ) ) {
			$component->set_children( hydrate_components( $component->get_children() ) );
		}

		// Reset context to where it was before hydration.
		get_template_context()->reset();

		// Convert text nodes to actual text notes.
		// @todo there's _definitely_ a better way to do this, but certain
		// Irving core functionality won't work without this hack.
		if ( 'irving/text' === $component->get_name() ) {
			$hydrated[] = $component->get_config( 'content' );
		} else {
			$hydrated[] = $component->jsonSerialize();
		}
	};

	return $hydrated;
}

/**
 * Validate and typecast a component.
 *
 * @param array $component Component.
 * @return Component|false A Component class instance or false on failure.
 */
function setup_component( $component ) {

	if ( $component instanceof Component ) {
		return $component;
	}

	// Convert strings to text components.
	if ( is_string( $component ) ) {
		$component = [
			'name' => 'irving/text',
			'config' => [
				'content' => $component,
			],
		];
	}

	// Components must have names.
	if ( ! isset( $component['name'] ) ) {
		return false;
	}

	$component = parse_config_from_registry( $component );

	return new Component( $component['name'], $component );
}

/**
 * Fill out a component args array from registered values.
 *
 * @param array $component An array of component arguments.
 * @return array A parsed array using registered values.
 */
function parse_config_from_registry( array $component ) {
	$registered = WP_Irving\get_registry()->get_registered_component( $component['name'] );

	if ( empty( $registered ) ) {
		return $component;
	}

	// Loop through all registered config keys and set them from passed
	// values if the value is valid, otherwise try using the default value.
	$parsed_config = [];

	$type_callbacks = [
		'array'   => 'is_array',
		'bool'    => 'is_bool',
		'int'     => 'is_int',
		'integer' => 'is_int',
		'number'  => 'is_numeric',
		'string'  => 'is_string',
		'text'    => 'is_string',
	];

	// Loop through registered config.
	foreach ( ( $registered['config'] ?? [] ) as $key => $atts ) {

		// If the config's type is not registered, throw a fatal.
		if ( ! isset( $type_callbacks[ $atts['type'] ] ) ) {
			wp_die(
				sprintf(
					// Translators: %1$s - Config key name, %2$s - Incorrect type, %3$s - Component namme, %4$s allowed types as string.
					esc_html__( 'The `%1$s` key is registered as `%2$s` for component `%3$s`\'. It must be one of [ %4$s ].', 'wp-irving' ),
					esc_html( $key ),
					esc_html( $atts['type'] ),
					esc_html( $component['name'] ),
					esc_html(
						implode(
							', ',
							array_map(
								function( $callback_key ) {
									return sprintf(
										'\'%1$s\'', // Wrap the key in single quotes.
										$callback_key
									);
								},
								array_keys( $type_callbacks )
							)
						)
					)
				)
			);
		}

		// This value has been set and a sanitize callback exists.
		if (
			isset( $component['config'][ $key ] )
			&& ( call_user_func( $type_callbacks[ $atts['type'] ], $component['config'][ $key ] ) )
		) {
			$parsed_config[ $key ] = $component['config'][ $key ];
		} elseif ( isset( $atts['default'] ) ) {
			$parsed_config[ $key ] = $atts['default'];
		}
	}

	$component['config'] = $parsed_config;

	$registered_property = [
		'callback',
		'provides_context',
		'theme_options',
		'use_context',
	];

	// Hydrate the rest of the component from the registry.
	foreach ( $registered_property as $prop ) {
		$component[ $prop ] = $registered[ $prop ] ?? [];
	}

	return $component;
}

/**
 * Pull in template parts.
 *
 * @param array $component Component.
 * @return array
 */
function hydrate_template_parts( $component ) {

	// Check if this is a template part.
	if ( 'template-parts' !== $component->get_namespace() ) {
		return false;
	}

	$template_part_name = substr( $component->get_name(), strpos( $component->get_name(), '/' ) + 1 );

	$template = locate_template_part( $template_part_name );

	// Bail early if no template is found.
	if ( ! $template ) {
		return false;
	}

	$template_data = prepare_data_from_template( $template );

	// Handle template parts with only one component rather than an array of
	// components.
	if ( isset( $template_data['name'] ) ) {
		$template_data = [ $template_data ];
	}

	return hydrate_components( $template_data );
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
		$context->set( [ 'irving/post' => get_the_ID() ] );
	}

	return $context;
}
