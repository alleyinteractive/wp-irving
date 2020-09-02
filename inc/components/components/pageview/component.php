<?php
/**
 * Google Tag Manager pageview.
 *
 * Insert a Google Tag Manager pageview event and metadata.
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
					'post_id' => $config['post_id']
				]
			);
		},
	]
);
