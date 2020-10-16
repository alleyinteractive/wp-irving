<?php
/**
 * Manage static assets.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Enqueues editor styles.
 */
function enqueue_editor() {
	wp_enqueue_script(
		'wp-irving-block-styles',
		home_url( '/blockEditor.js' ),
		[ 'wp-compose', 'wp-hooks' ],
		apply_filters( 'wp_irving_editor_styles_version', '1.0.0' ),
		true
	);

	// Get value from a component to ensure it functions as it would in Irving app (including camelCasing).
	$site_theme_provider = new \WP_Irving\Components\Component(
		'irving/site-theme',
		[
			'config' => [
				'theme' => \WP_Irving\Templates\get_site_theme(),
			],
		]
	);
	$site_theme_array    = $site_theme_provider->to_array();

	wp_localize_script(
		'wp-irving-block-styles',
		'irvingSiteTheme',
		$site_theme_array['config']->theme
	);
};

add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor' );
