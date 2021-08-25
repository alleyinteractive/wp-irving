<?php
/**
 * Static Coral Comment Count.
 *
 * This component is intended for use in conjunction with the cron job in the
 * Coral integration class, which can be enabled from the Irving Integrations
 * settings page. Instead of loading the Coral script on the FE to fetch
 * comment counts (as is done in the `irving/coral-comment-count` component),
 * counts are fetched via API on a cron schedule and stored in post meta.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Register the component.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function ( array $config ): array {
			// Retrieve the comment count from post meta.
			$config['count'] = (int) get_post_meta( $config['post_id'], 'coral_comment_count', true );

			// Update the text to be singular if there is only one comment.
			if ( 1 === $config['count'] ) {
				$config['count_text'] = __( 'Comment', 'wp-irving' );
			}

			// Set the article URL.
			$config['article_URL'] = get_the_permalink( $config['post_id'] );

			return $config;
		},
	]
);
