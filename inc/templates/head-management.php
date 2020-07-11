<?php
/**
 * Automatically inject an `irving/head` component to manage the document
 * <head>.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

use WP_Irving\Components\Component;
use WP_Irving\Utils;

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

	// Disable Helmet management via filter.
	if ( ! apply_filters( 'wp_irving_setup_head', true ) ) {
		return $data;
	}

	// Unshift a helmet component to the top of the `defaults` array.
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

	// Unshift a helmet component to the top of the `page` array.
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
 * Parse WP's favicon markup and inject it into the Helmet component.
 *
 * @param array $children Children for the <head>.
 * @return array
 */
function inject_favicon( array $children ): array {
	return array_merge(
		$children,
		\WP_Irving\Utils\html_to_components( get_favicon_markup(), [ 'link', 'meta' ] )
	);
}
add_action( 'wp_irving_defaults_head_component_children', __NAMESPACE__ . '\inject_favicon' );
