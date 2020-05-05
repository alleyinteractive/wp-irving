<?php
/**
 * Registration for archive related commponents.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Get the archive title.
 *
 * @param array $component Component
 * @return string
 */
register_component(
	'archive/title',
	[
		'callback' => function( $component ) {
			return get_the_archive_title();
		},
		'data_provider' => [
			'postId' => [
				'type' => 'integer',
			],
		],
	]
);
