<?php
/**
 * Post title.
 *
 * Get the post title.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function ( array $config ): array {

			// Post ID is set via context and should always have a value.
			$title = get_the_title( $config['post_id'] );

			$config['content'] = html_entity_decode( $title, ENT_QUOTES, get_bloginfo( 'charset' ) );

			return $config;
		},
	]
);
