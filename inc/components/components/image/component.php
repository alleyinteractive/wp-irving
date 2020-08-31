<?php
/**
 * Image
 *
 * Output an image.
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
		'config_callback' => function( array $config ): array {

			// Bail early if passed a `src` URL.
			if ( isset( $config['src'] ) ) {
				return $config;
			}

			// Hydrate data from an ID.
			if ( isset( $config['id'] ) ) {
				$atts = wp_get_attachment_image_src( $config['id'], $config['size'] );

				$config['src']    = $atts[0];
				$config['width']  = $atts[1];
				$config['height'] = $atts[2];
				$config['alt']    = $config['alt'] ?? (string) get_post_meta( $config['id'], '_wp_attachment_image_alt', true );
				$config['srcset'] = wp_get_attachment_image_srcset( $config['id'], $config['size'] );
			}

			return $config;
		},
	]
);
