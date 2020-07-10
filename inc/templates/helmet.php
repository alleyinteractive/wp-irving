<?php
/**
 * Helmet helpers to manage the <head> in templates.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

use WP_Irving\Components\Component;
use WP_Irving\Utils;

/**
 * Manage the template head by automatically inserting Helmet tags.
 *
 * @param array            $data    Data object to be hydrated by templates.
 * @param \WP_Query        $query   The current WP_Query object.
 * @param string           $context The context for this request.
 * @param string           $path    The path for this request.
 * @param \WP_REST_Request $request WP_REST_Request object.
 * @return array The updated endpoint data.
 */
function setup_helmet(
	array $data,
	\WP_Query $query,
	string $context,
	string $path,
	\WP_REST_Request $request
): array {

	// Disable Helmet management via filter.
	if ( ! apply_filters( 'wp_irving_setup_helmet', true ) ) {
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
	return ob_get_clean();
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
		\WP_Irving\Utils\html_to_components( get_favicon_markup(), [ 'link' ] )
	);
}
add_action( 'wp_irving_defaults_head_component_children', __NAMESPACE__ . '\inject_favicon' );
