<?php
/**
 * Registration for Gutenberg Block related commponents.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Convert the core paragraph block to an HTML component.
 *
 * @return array
 */
register_component(
	'core/paragraph',
	[
		'callback' => function( $component ) {
			$component['name'] = 'irving/html';
			return $component;
		},
	]
);
