<?php
/**
 * Post featured image.
 *
 * Get the post featured image.
 *
 * @todo Add filters to make customizing this easier.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_Irving\Component;

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
\WP_Irving\get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function( Component $component ): Component {

			// Get the post ID from a context provider.
			$post_id = $component->get_config( 'post_id' );

			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				return $component;
			}

			$thumbnail_id = get_post_thumbnail_id( $post_id );

			return $component
				->set_config( 'alt', (string) get_post_alt( $post_id ) )
				->set_config( 'caption', (string) wp_get_attachment_caption( $thumbnail_id ) )
				->set_config( 'credit', (string) get_post_meta( $thumbnail_id, 'caption', true ) )
				->set_config( 'src', (string) get_the_post_thumbnail_url( $post_id ) );
		},
	]
);

/**
 * Determine an `alt` attribute value for this image.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function get_post_alt( int $post_id ): string {

	// Validate the post exists.
	if ( ! get_post( $post_id ) instanceof \WP_Post ) {
		return '';
	}

	// Get the alt.
	$alt = get_post_meta( $post_id, '_wp_attachment_image_alt', true );
	if ( ! empty( $alt ) ) {
		return esc_attr( $alt );
	}

	// Fallback to caption.
	$alt_from_caption = wp_get_attachment_caption( get_post_thumbnail_id( $post_id ) );
	if ( ! empty( $alt_from_caption ) ) {
		return esc_attr( $alt_from_caption );
	}

	// Fallback to excerpt.
	$alt_from_excerpt = apply_filters( 'the_excerpt', get_the_excerpt( $post_id ) ); // phpcs:ignore
	if ( ! empty( $alt_from_excerpt ) ) {
		return esc_attr( $alt_from_excerpt );
	}

	return '';
}
