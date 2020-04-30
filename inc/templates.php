<?php
/**
 * Templates.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

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
	}

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
 * Return a located template file.
 *
 * @param array $templates A list of possible template files to load.
 * @return string The path to the found template.
 */
function locate_template( array $templates, $folder = '/templates/' ): string {
	$template_path = STYLESHEETPATH . $folder;

	/**
	 * Filter the path to Irving templates.
	 *
	 * @param string $template_path The full path to the template folder.
	 * @param array  $templates     A list of template files to locate.
	 */
	apply_filters( 'wp_irving_template_path', $template_path, $templates );

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
