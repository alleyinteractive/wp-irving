<?php
/**
 * Site theme.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

use WP_Irving\Components\Component;

/**
 * Dynamically manage an `irving/site-theme` component used as a context
 * provider for frontend styles.
 *
 * @todo Integrate with WordPress Global Styles.
 *
 * @param array $data Data object to be hydrated by templates.
 * @return array The updated endpoint data.
 */
function setup_site_theme_provider( array $data ): array {

	// Disable site theme via filter.
	if ( ! apply_filters( 'wp_irving_enable_site_theme', true ) ) {
		return $data;
	}

	// Get and validate the site_theme.
	$site_theme = get_site_theme();
	if ( empty( $site_theme ) ) {
		return $data;
	}

	$data['providers'][] = new Component(
		'irving/site-theme',
		[
			'config' => [
				'theme' => $site_theme,
			],
		]
	);

	return $data;
}

/**
 * Get the site theme, or a specific value using dot syntax.
 *
 * @param string $selector Selector in dot syntax.
 * @param mixed  $default  Default value if selector fails.
 * @return array|string
 */
function get_site_theme( string $selector = '', $default = null ) {

	// Get cached site theme value, if available.
	$cache_key = 'wp_irving_site_theme';
	$theme     = wp_cache_get( $cache_key );
	if ( false === $theme ) {
		/**
		 * Filter to modify the site theme.
		 *
		 * @var array
		 */
		$theme = apply_filters( 'wp_irving_setup_site_theme', get_site_theme_from_json_files() );
		// Cache the value.
		wp_cache_set( $cache_key, $theme, '', 1 * MINUTE_IN_SECONDS );
	}

	// Get the entire theme.
	if ( empty( $selector ) ) {
		return $theme;
	}

	$value = $theme;

	// Loop through each segment of the selector.
	foreach ( explode( '.', $selector ) as $segment ) {

		// If it's not an array, or the key doesn't exist, return the default instead.
		if (
			! is_array( $value )
			|| ! array_key_exists( $segment, $value )
		) {
			return $default;
		}

		// Update value with the next level.
		$value = &$value[ $segment ];
	}

	// Support recursively getting a single value.
	if ( is_string( $value ) ) {
		do {
			$default = $value;
			$value   = get_site_theme( $value, $default );
		} while ( $default !== $value & is_string( $value ) );
	}

	// Return the value found at the final selector segment.
	return $value;
}

/**
 * Return a single site theme by traversing multiple directories of JSON files
 * and recursively merging them.
 *
 * @return bool|array Site theme array, or false if something went wrong.
 */
function get_site_theme_from_json_files() {

	// Default paths for parent and child themes, where the child theme takes precedence.
	$default_paths = [
		get_template_directory() . '/site-theme/', // Support parent themes.
		get_stylesheet_directory() . '/site-theme/', // Support child themes.
	];

	// Ensure unique paths (so that non-child themes don't run twice).
	$default_paths = array_unique( $default_paths );

	/**
	 * Filter the paths to directories containing site theme JSON files.
	 *
	 * @param string[] $default_paths Paths to files.
	 */
	$paths = apply_filters( 'wp_irving_site_theme_json_directory_paths', $default_paths );

	return array_reduce(
		$paths,
		function( $site_theme, $path ) {

			// Get the site theme for this path.
			$site_theme_for_path = get_site_theme_from_path_to_json_files( $path );

			// Valid theme data.
			if (
				is_array( $site_theme_for_path )
				&& ! empty( $site_theme_for_path )
			) {
				$site_theme = array_replace_recursive( $site_theme, $site_theme_for_path );
			}

			return $site_theme;
		},
		[]
	);
}

/**
 * Loop through some directories importing components and registering them.
 *
 * @param string $path Path to files.
 * @return array|bool Site theme array, or false if something went wrong.
 */
function get_site_theme_from_path_to_json_files( string $path ) {

	// Validate the path.
	if ( ! is_dir( $path ) ) {
		return [];
	}

	// Recursively loop through $path, including anything that ends in index.php.
	$directory_iterator = new \RecursiveDirectoryIterator( $path );
	$iterator           = new \RecursiveIteratorIterator( $directory_iterator );
	$regex              = new \RegexIterator( $iterator, '/.+\/(.+)\.json$/', \RecursiveRegexIterator::ALL_MATCHES );

	// Include each index.php entry point.
	foreach ( $regex as $results ) {

		// Validate the path.
		$file_path = $results[0][0] ?? '';
		if ( ! file_exists( $file_path ) ) {
			continue;
		}

		// Get the name.
		$file_name = $results[1][0] ?? '';

		// Validate name.
		if ( empty( $file_name ) ) {
			wp_die(
				sprintf(
					// Translators: %1$s Template path.
					esc_html__( 'Error: Empty filename found in %1$s.', 'wp-irving' ),
					esc_html( $file_path )
				)
			);
			return false;
		}

		// Decode the content.
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		$styles = file_get_contents( $file_path );
		$styles = json_decode( $styles, true );

		// Validate JSON.
		if ( is_null( $styles ) ) {
			wp_die(
				sprintf(
					// Translators: %1$s: Error message, %2$s: File path.
					esc_html__( 'Error: %1$s found in %2$s.', 'wp-irving' ),
					esc_html( json_last_error_msg() ),
					esc_html( $file_path )
				)
			);
			return false;
		}

		$theme[ $file_name ] = $styles;
	}

	return $theme;
}
