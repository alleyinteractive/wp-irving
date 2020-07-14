<?php
/**
 * Post timestamp.
 *
 * Get the post's timestamp(s).
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
		'config_callback' => function( array $config ): array {

			$post_id = $config['post_id'];

			// Bail if there is no post ID.
			if ( ! $post_id ) {
				return $config;
			}

			$post_date     = get_the_date( $config['post_date_format'], $post_id );
			$modified_date = get_the_modified_date( $config['modified_date_format'], $post_id );

			$content = sprintf(
				/**
				 * Translators:
				 * %1$s - Published timestamp.
				 * %2$s - Modified timestamp.
				 */
				$config['content_format'],
				esc_html( $post_date ),
				esc_html( $modified_date ),
			);

			return array_merge(
				$config,
				[
					'content'       => $content,
					'modified_date' => $modified_date,
					'post_date'     => $post_date,
				]
			);
		},
	]
);
