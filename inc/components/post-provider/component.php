<?php
/**
 * Post provider.
 *
 * Provide post context to children components.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Irving\Component;

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component.
 */
\WP_Irving\get_registry()->register_component_from_config( __DIR__ . '/component' );
