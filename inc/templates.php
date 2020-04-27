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
 * @param WP_Query $query   The current WP_Query object.
 * @param array    $data    Data object to be hydrated by templates.
 * @param string   $context The context for this request.
 *
 * @return array A hydrated data object.
 */
function load_template( array $data, WP_Query $query, string $context ) : array {
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
	 * Filters the path of the current template before including it.
	 *
	 * @since 3.0.0
	 *
	 * @param string $template The path of the template to include.
	 */
	$template = apply_filters( 'wp_irving_template_include', $template, $query );

	if ( $template ) {
		ob_start();
		include $template;
		$data = array_merge( $data, json_decode( ob_get_clean(), true ) );
	}

	// Include defaults from a template if this is a server render.
	if ( 'site' === $context ) {
		$defaults = locate_template( [ 'defaults.php' ] );

		if ( $defaults ) {
			ob_start();
			include $defaults;
			$data = array_merge( $data, json_decode( ob_get_clean(), true ) );
		}
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
 * @see    https://core.trac.wordpress.org/ticket/48175
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
function locate_template( $templates ) {
	$template_path = STYLESHEETPATH . '/templates/';

	/**
	 * Filter the path to Irving templates.
	 *
	 * @param string $template_path The full path to the template folder.
	 * @param array  $templates     A list of template files to locate.
	 */
	apply_filters( 'wp_irving_template_path', $template_path, $templates );

	$located = '';

	foreach ( $templates as $template ) {
		// If the PHP template file exists, use it.
		if ( file_exists( $template_path . $template ) ) {
			$located = $template_path . $template;
			break;
		}

		// Convert the PHP filename to the relevant .json filename.
		$template_json = wp_basename( $template, '.php' ) . '.json';

		// Next, try a JSON version of the template file.
		if ( file_exists( $template_path . $template_json ) ) {
			$located = $template_path . $template_json;
			break;
		}
	}

	return $located;
}
