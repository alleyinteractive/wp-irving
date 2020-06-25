<?php
/**
 * Site theme.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

use WP_Irving;
use WP_Irving\Component;

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
	if ( ! apply_filters( 'wp_irving_setup_site_theme', true ) ) {
		return $data;
	}

	$data['providers'][] = new Component(
		'irving/site-theme',
		[
			'config' => [
				'theme' => get_site_theme(),
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
function get_site_theme( $selector = '', $default = null ) {

	/**
	 * Filter to modify the site theme.
	 *
	 * @var array
	 */
	$theme = apply_filters(
		'wp_irving_setup_site_theme',
		[
			/**
			 * Default breakpoint maps.
			 */
			'breakpoints' => [
				'xs' => '0px',
				'sm' => '600px',
				'md' => '960px',
				'lg' => '1280px',
				'xl' => '1920px',
			],
			/**
			 * Default color options.
			 *
			 * @see https://material-ui.com/customization/palette/
			 */
			'colors'      => [
				'primary'   => [
					'light' => '#4791db',
					'main'  => '#1976d2',
					'dark'  => '#115293',
				],
				'secondary' => [
					'light' => '#e33371',
					'main'  => '#dc004e',
					'dark'  => '#9a0036',
				],
				'error'     => [
					'light' => '#e57373',
					'main'  => '#f44336',
					'dark'  => '#d32f2f',
				],
				'warning'   => [
					'light' => '#ffb74d',
					'main'  => '#ff9800',
					'dark'  => '#f57c00',
				],
				'info'      => [
					'light' => '#64b5f6',
					'main'  => '#2196f3',
					'dark'  => '#1976d2',
				],
				'success'   => [
					'light' => '#81c784',
					'main'  => '#4caf50',
					'dark'  => '#388e3c',
				],
				'text'      => [
					'primary'   => 'rgba(0, 0, 0, 0.87)',
					'secondary' => 'rgba(0, 0, 0, 0.54)',
					'disabled'  => 'rgba(0, 0, 0, 0.38)',
				],
			],
			/**
			 * Default font families.
			 *
			 * @see https://css-tricks.com/snippets/css/font-stacks/
			 */
			'fonts'       => [
				'body'                       => [
					'variant1' => 'Baskerville, "Times New Roman", Times, serif',
					'variant2' => 'Garamond, "Hoefler Text", "Times New Roman", Times, serif',
					'variant3' => 'Geneva, "Lucida Sans", "Lucida Grande", "Lucida Sans Unicode", Verdana, sans-serif',
					'variant4' => 'GillSans, Calibri, Trebuchet, sans-serif',
				],
				'headlines'                  => [
					'variant1' => 'Georgia, Times, ‘Times New Roman’, serif',
					'variant2' => 'Palatino, ‘Palatino Linotype’, ‘Hoefler Text’, Times, ‘Times New Roman’, serif',
					'variant3' => 'Tahoma, Verdana, Geneva',
					'variant4' => 'Trebuchet, Tahoma, Arial, sans-serif',
				],
				'helvetica_arial_sans_serif' => 'Frutiger, "Frutiger Linotype", Univers, Calibri, "Gill Sans", "Gill Sans MT", "Myriad Pro", Myriad, "DejaVu Sans Condensed", "Liberation Sans", "Nimbus Sans L", Tahoma, Geneva, "Helvetica Neue", Helvetica, Arial, sans-serif',
				'impact_san_sserif'          => 'Impact, Haettenschweiler, "Franklin Gothic Bold", Charcoal, "Helvetica Inserat", "Bitstream Vera Sans Bold", "Arial Black", sans-serif',
				'modern_georgia_serif'       => 'Constantia, "Lucida Bright", Lucidabright, "Lucida Serif", Lucida, "DejaVu Serif", "Bitstream Vera Serif", "Liberation Serif", Georgia, serif',
				'monospace'                  => 'Consolas, "Andale Mono WT", "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", "DejaVu Sans Mono", "Bitstream Vera Sans Mono", "Liberation Mono", "Nimbus Mono L", Monaco, "Courier New", Courier, monospace',
				'times_new_roman'            => 'Cambria, "Hoefler Text", Utopia, "Liberation Serif", "Nimbus Roman No9 L Regular", Times, "Times New Roman", serif',
				'traditional_serif'          => '"Palatino Linotype", Palatino, Palladio, "URW Palladio L", "Book Antiqua", Baskerville, "Bookman Old Style", "Bitstream Charter", "Nimbus Roman No9 L", Garamond, "Apple Garamond", "ITC Garamond Narrow", "New Century Schoolbook", "Century Schoolbook", "Century Schoolbook L", Georgia, serif',
				'trebuchet_sans_serif'       => '"Segoe UI", Candara, "Bitstream Vera Sans", "DejaVu Sans", "Bitstream Vera Sans", "Trebuchet MS", Verdana, "Verdana Ref", sans-serif',
				'verdana-based_sans_serif'   => 'Corbel, "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "DejaVu Sans", "Bitstream Vera Sans", "Liberation Sans", Verdana, "Verdana Ref", sans-serif',
			],
			/**
			 * Default spacing values.
			 */
			'spacing'     => [
				'gutter' => '1.25rem',
			],
		]
	);

	// Get the entire theme.
	if ( empty( $selector ) ) {
		return $theme;
	}

	$value = $theme;

	// Loop through each segment of the selector.
	foreach ( explode( '.', $selector ) as $segment ) {

		// If it's not an array, or the key doesn't exisst, return the default instead.
		if (
			! is_array( $value )
			|| ! array_key_exists( $segment, $value )
		) {
			return $default;
		}

		// Update value with the next level.
		$value = &$value[ $segment ];
	}

	// Return the value found at the final selector segment.
	return $value;
}
