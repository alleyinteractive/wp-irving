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

use WP_Post;

/**
 * Register the component and callback.
 */
register_component_from_config(
	__DIR__ . '/component',
	[
		'config_callback' => function( array $config ): array {
			$post_id = $config['post_id'] ?: 0;

			if ( ! $post_id ) {
				return $config;
			}

			// Attempt to get the featured image.
			$thumbnail_id = null;
			if ( has_post_thumbnail( $post_id ) ) {
				$thumbnail_id = get_post_thumbnail_id( $post_id );
			}

			// Use the theme fallback.
			if ( is_null( $thumbnail_id ) && $config['use_theme_fallback'] ) {

				// Get and validate the fallback attachment id.
				$fallback_thumbnail_id = apply_filters( 'wp_irving_fallback_attachment_id', get_theme_mod( 'wp_irving-fallback_image' ) );
				if ( wp_attachment_is_image( $fallback_thumbnail_id ) ) {
					$thumbnail_id = $fallback_thumbnail_id;
				}
			}

			// No valid image found.
			if ( is_null( $thumbnail_id ) ) {
				return $config;
			}

			$config['caption'] = wp_get_attachment_caption( $thumbnail_id );
			$config['credit']  = get_post_meta( $thumbnail_id, 'credit', true );

			$image_atts = get_image_component_attributes( $thumbnail_id, $config['size'] );

			// Override empty or unset config values with WP data.
			foreach ( $image_atts as $key => $value ) {
				if ( ! isset( $config[ $key ] ) || empty( $config[ $key ] ) ) {
					$config[ $key ] = $value;
				}
			}

			return $config;
		},
	]
);
