<?php
/**
 * Link.
 *
 * Custom anchor.
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
		'config_callback' => function( array $config ) : array {
			if ( false !== strpos( $config['href'], '#' ) ) {
				return $config;
			}

			if ( ! empty( $config['fragment'] ) ) {
				$config['href'] .= '#' . $config['fragment'];
			}

			return $config;
		},
	]
);
