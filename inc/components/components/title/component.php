<?php
/**
 * Head.
 *
 * Manage the <head>.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Register the component and callback.
 */
get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function( $config ) {
			return $config;
		},
		'children_callback' => function( $children, $config ): array {
			return $children;
		},
	]
);
