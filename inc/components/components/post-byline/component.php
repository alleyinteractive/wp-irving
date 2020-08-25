<?php
/**
 * Post byline.
 *
 * Display a list of content authors.
 *
 * @todo Add support for CAP and Byline Manager plugins.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Post;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'children_callback' => function( array $children, array $config ): array {
			$post_id = $config['post_id'];
			$post    = get_post( $post_id );

			if ( ! $post instanceof WP_Post ) {
				return $children;
			}

			// Get the post author, and add a link to their author archive.
			$author_id   = get_post_field( 'post_author', $post_id );
			$author_link = get_author_posts_url( $author_id );

			if ( ! empty( $author_link ) ) {
				$children[] = new Component(
					'irving/link',
					[
						'config'   => [
							'href' => get_author_posts_url( $author_id ),
						],
						'children' => [
							[
								'name'   => 'irving/text',
								'config' => [
									'content' => get_the_author_meta( 'display_name', $author_id ),
								],
							],
						],
					]
				);
			} else {
				$children[] = new Component(
					'irving/text',
					[
						'config' => [
							'content' => get_the_author_meta( 'display_name', $author_id ),
						],
					]
				);
			}


			return $children;
		},
	]
);
