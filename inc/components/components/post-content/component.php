<?php
/**
 * Post content.
 *
 * Get the post concept.
 *
 * @todo Update the output to handle classic HTML and Gutenberg blocks.
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

			// Bail early if the post ID is not set.
			if ( ! $post_id ) {
				return $config;
			}

			/**
			 * Taken directly from Gutenberg.
			 *
			 * @see https://github.com/WordPress/gutenberg/blob/30cd85aebe14eee995e3162f09d31f4d4786f101/packages/block-library/src/post-content/index.php#L23
			 */
			$config['content'] = apply_filters( 'the_content', str_replace( ']]>', ']]&gt;', get_the_content( null, false, $post_id ) ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			return $config;
		},
	]
);
