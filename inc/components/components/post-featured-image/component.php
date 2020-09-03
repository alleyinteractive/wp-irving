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

			$thumbnail_id = get_post_thumbnail_id( $post_id );

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
