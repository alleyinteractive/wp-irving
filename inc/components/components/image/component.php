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
				$image_atts = get_image_component_attributes( $config['id'], $config['size'] );

				// Override empty or unset config values with WP data.
				foreach ( $image_atts as $key => $value ) {
					if ( ! isset( $config[ $key ] ) || empty( $config[ $key ] ) ) {
						$config[ $key ] = $value;
					}
				}
			}

			return $config;
		},
	]
);
