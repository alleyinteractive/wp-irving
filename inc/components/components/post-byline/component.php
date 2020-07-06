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

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function( Component $component ): Component {

			// Get the post ID from a context provider.
			$post_id = $component->get_config( 'post_id' );

			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				return $component;
			}

			// Get the post author, and add a link to their author archive.
			$author_id = get_post_field( 'post_author', $post_id );
			$component
				->append_child(
					( new Component( 'irving/link' ) )
						->set_config( 'href', get_author_posts_url( $author_id ) )
						->set_child(
							new Component(
								'irving/text',
								[
									'config' => [
										'content' => get_the_author_meta( 'display_name', $author_id ),
									],
								]
							)
						)
				);

			return $component;
		},
	]
);
