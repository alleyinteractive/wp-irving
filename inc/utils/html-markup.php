<?php
/**
 * HTML utilities.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Utils;

use WP_Irving\Components;

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

			$components[] = new Components\Component( $tag, $component_args );
		}
	}

	// phpcs:enable WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

	return $components;
}
