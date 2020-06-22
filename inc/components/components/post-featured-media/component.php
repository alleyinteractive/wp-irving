<?php
/**
 * Post featured media.
 *
 * Display the post featured media.
 *
 * @todo Update to remove all material UI.
 * @todo Consider creating a React component for this functionality.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function( Component $component ): Component {

			// Get the post ID from a context provider.
			$post_id = $component->get_config( 'post_id' );

			// Get and validate image url.
			$image_url = get_the_post_thumbnail_url( $post_id );
			if ( empty( $image_url ) ) {
				return $component;
			}

			$wrapper = new Component( 'irving/container' );
			$wrapper->set_config( 'max_width', 'lg' );
			$wrapper->set_config( 'style', [ 'bottom-margin' => '1rem' ] );

			$wrapper->append_child(
				( new Component( 'material/card-content' ) )
					->set_config( 'gutter_bottom', true )
					->set_child(
						( new Component( 'material/card-media' ) )
							->set_config( 'image', $image_url )
							->set_config(
								'style',
								[
									'bottom_margin' => '1rem',
									'height'        => '450px',
								]
							)
					)
			);

			$caption = wp_get_attachment_caption( get_post_thumbnail_id( $post_id ) );
			if ( ! empty( $caption ) ) {
				$wrapper->append_child(
					( new Component( 'irving/html' ) )
						->append_child( $caption )
				);
			}

			return $component->set_child( $wrapper );
		},
	]
);
