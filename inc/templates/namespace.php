<?php
/**
 * Template functionality.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

use WP_Irving\Components;
use WP_Irving\Components\Component;
use WP_Irving\REST_API\Components_Endpoint;
use WP_Query;
use WP_REST_Request;

/**
 * Bootstrap filters.
 */
function bootstrap() {
	add_filter( 'wp_irving_components_route', __NAMESPACE__ . '\\load_template', 10, 6 );
	add_action( 'wp_irving_component_children', __NAMESPACE__ . '\\inject_favicon', 10, 3 );
}

/**
 * Shallow template loader using core's template hierarchy.
 *
 * Based on wp-includes/template-loader.php.
 *
 * @param array               $data     Data object to be hydrated by
 *                                      templates.
 * @param WP_Query            $query    The current WP_Query object.
 * @param string              $context  The context for this request.
 * @param string              $path     The path for this request.
 * @param WP_REST_Request     $request  WP_REST_Request object.
 * @param Components_Endpoint $endpoint Current class instance.
 * @return array A hydrated data object.
 */
function load_template(
	array $data,
	WP_Query $query,
	string $context,
	string $path,
	WP_REST_Request $request,
	Components_Endpoint $endpoint
): array {

	$template_path = get_template_path( $query );

	if ( $template_path ) {
		$data = array_merge( $data, prepare_data_from_template( $template_path ) );
	}

	// Include defaults from a template if this is a server render.
	if ( 'site' === $context ) {
		$defaults = locate_template( [ 'defaults' ] );

		if ( $defaults ) {
			$data = array_merge( $data, prepare_data_from_template( $defaults, 'defaults' ) );
		}
	}

	// Automatically setup an admin bar component.
	$data = \WP_Irving\setup_admin_bar( $data, $query, $context, $path, $request, $endpoint );

	// Automatically setup the <Head> component.
	$data = setup_head( $data, $query, $context, $path, $request );

	// Setup a global style provider.
	$data = setup_site_theme_provider( $data );

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

	$tag_templates = [
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
	];

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
 * @param string $template_path Full path to template.
 * @return array An associative array prepared for an Irving REST Response.
 */
function prepare_data_from_template( string $template_path ): array {
	$data = [];

	$type = pathinfo( $template_path, PATHINFO_EXTENSION );

	// Load template data.
	ob_start();
	include $template_path;
	$contents = ob_get_clean();

	// Hydrate the template data.
	switch ( $type ) {
		case 'json':
			// Attempt to json decode it.
			$data = json_decode( $contents, true );

			// If a template part only includes a single component,
			// we need to wrap it in another array.
			if ( count( $data ) > 0 && ! isset( $data[0] ) ) {
				$data = [ $data ];
			}

			// Check for errors during decoding.
			if ( json_last_error() ) {
				wp_send_json_error(
					sprintf(
						// Translators: %1$s: Error message, %2$s Template path.
						esc_html__( 'Error: %1$s found in %2$s.', 'wp-irving' ),
						esc_html( json_last_error_msg() ),
						esc_html( $template_path )
					),
					500
				);
			}

			// Hydrate page and default data separately.
			if ( isset( $data['page'] ) || isset( $data['defaults'] ) ) {
				if ( isset( $data['page'] ) ) {
					$data['page'] = hydrate_template( $data['page'] );
				}
				if ( isset( $data['defaults'] ) ) {
					$data['defaults'] = hydrate_template( $data['defaults'] );
				}
			} else {
				$data = hydrate_template( $data );
			}
			break;
		case 'php':
		default:
			// Assume PHP templates are already hydrated.
			break;
	}

	return $data;
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
		add_filter(
			"{$type}_template_hierarchy",
			function ( $templates ) use ( $type ) {
				add_filter(
					"{$type}_template",
					function () use ( $templates ) {
						return locate_template( $templates );
					}
				);

				return [];
			}
		);
	}
}

/**
 * Return the full path to a template file.
 *
 * @param array $templates A list of possible template files to load.
 * @return string The path to the found template.
 */
function locate_template( array $templates ): string {
	$template_path = get_stylesheet_directory() . '/templates/';

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

		// Support child themes if the template path is in the theme.
		if (
			is_child_theme()
			&& 0 === strpos( $template_path, get_stylesheet_directory() )
		) {
			$child_template_path = str_replace( get_stylesheet_directory(), get_template_directory(), $template_path );

			foreach ( $filetypes as $type ) {
				// Ensure filtered template paths are slashed.
				$path = trailingslashit( $child_template_path ) . $template_base . '.' . $type;

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

	$template_part_path = get_stylesheet_directory() . '/template-parts/';

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

	// Support child themes if the template part path is in the theme.
	if (
		is_child_theme()
		&& 0 === strpos( $template_part_path, get_stylesheet_directory() )
	) {
		$child_template_part_path = str_replace( get_stylesheet_directory(), get_template_directory(), $template_part_path );

		foreach ( $filetypes as $type ) {
			// Ensure filtered template paths are slashed.
			$path = trailingslashit( $child_template_part_path ) . $template_base . '.' . $type;

			// If the file is located, break out of filetype loop.
			if ( file_exists( $path ) ) {
				return $path;
			}
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
 * Hydrate a JSON template into a REST response.
 *
 * @param array $data A JSON array of template data.
 * @return array a data array ready for a REST response.
 */
function hydrate_template( array $data ): array {

	$hydrated = [];

	foreach ( $data as $component ) {
		// First, handle template part matching.
		if (
			isset( $component['name'] ) &&
			0 === strpos( $component['name'], 'template-part/' )
		) {
			$template_data = hydrate_template_parts( $component );

			// If the component is converted to template data,
			// add hydrated components to the array and move on.
			if ( ! empty( $template_data ) ) {
				array_push( $hydrated, ...$template_data );
			}

			continue;
		}

		$component = new Component( $component['name'], $component );

		if ( ! is_wp_error( $component ) ) {
			$hydrated[] = $component;
		}
	};

	return $hydrated;
}


/**
 * Pull in template parts.
 *
 * @param array $data Template data.
 * @return array
 */
function hydrate_template_parts( array $data ): array {
	$template_part_name = substr( $data['name'], strpos( $data['name'], '/' ) + 1 );

	$template = locate_template_part( $template_part_name );

	// Bail early if no template is found.
	if ( ! $template ) {
		return [];
	}

	return prepare_data_from_template( $template );
}

/**
 * Manage the <head> by automatically inserting an `irving/head` component.
 *
 * @param array     $data    Data object to be hydrated by templates.
 * @param \WP_Query $query   The current WP_Query object.
 * @param string    $context The context for this request.
 * @return array The updated endpoint data.
 */
function setup_head(
	array $data,
	\WP_Query $query,
	string $context
): array {

	// Disable `irving/head` management via filter.
	if ( ! apply_filters( 'wp_irving_setup_head', true ) ) {
		return $data;
	}

	// Unshift a `irving/head` component to the top of the `defaults` array.
	if ( 'site' === $context ) {
		array_unshift(
			$data['defaults'],
			new Component(
				'irving/head',
				[
					'config' => [
						'context' => 'defaults',
					],
				]
			)
		);
	}

	// Unshift a `irving/head` component to the top of the `page` array.
	array_unshift(
		$data['page'],
		new Component(
			'irving/head',
			[
				'config' => [
					'context' => 'page',
				],
			]
		)
	);

	return $data;
}

/**
 * Capture the markup output by WordPress for the favicon.
 *
 * @return string
 */
function get_favicon_markup(): string {
	ob_start();
	wp_site_icon();
	return trim( ob_get_clean() );
}

/**
 * Parse WP's favicon markup and inject it into the `irving/head` component.
 *
 * @param array  $children Children for this component.
 * @param array  $config   Config for this component.
 * @param string $name     Name of this component.
 * @return array
 */
function inject_favicon( array $children, array $config, string $name ): array {

	// Ony run this action on the `irving/head` in a `page` context.
	if (
		'irving/head' !== $name
		|| 'page' !== ( $config['context'] ?? 'page' )
	) {
		return $children;
	}

	return array_merge(
		$children,
		Components\html_to_components( get_favicon_markup(), [ 'link', 'meta' ] )
	);
}
