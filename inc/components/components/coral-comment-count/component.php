<?php
/**
 * Coral embed.
 *
 * Insert a coral instance on a given template.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Irving\Integrations;

/**
 * Register the component.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function ( $config ) {
			// Bail early if there's no post ID.
			if ( ! $config['post_id'] ) {
				return $config;
			}

			return array_merge(
				$config,
				[
					'embed_URL'   => untrailingslashit( Integrations\get_option_value( 'coral', 'url' ) ),
					'article_URL' => get_the_permalink( $config['post_id'] ),
				]
			);
		},
	]
);
