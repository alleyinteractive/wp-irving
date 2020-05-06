<?php
/**
 * Registration for Material UI commponents.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Add the post thumbnail to a card.
 *
 * @return array
 */
register_component(
	'material/card-media',
	[
		'callback' => function( $component ) {

			if ( ! empty( $component['config']['image'] ?? '' ) ) {
				return $component;
			}

			$component['config']['image'] = get_the_post_thumbnail_url( $component['data_provider']['postId'] ?? get_the_ID() );
			return $component;
		},
	]
);
