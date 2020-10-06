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
		'1.0.0',
		true
	);
}

add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor' );