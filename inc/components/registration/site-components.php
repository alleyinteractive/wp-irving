<?php
/**
 * Registration for site commponents.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Get site name.
 *
 * @return string Name of the site.
 */
register_component(
	'bloginfo/name',
	[
		'callback' => function( $component ) {
			return get_bloginfo( 'name' );
		},
	]
);
