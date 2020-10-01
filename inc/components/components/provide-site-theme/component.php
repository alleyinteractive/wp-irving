<?php
/**
 * Provider for get_site_theme().
 *
 * Provide context from the Site Theme.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Irving\Templates;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function ( array $config ): array {
			$config['value'] = Templates\get_site_theme( $config['selector'] ?? '', $config['default'] ?? null );
			return $config;
		},
	]
);
