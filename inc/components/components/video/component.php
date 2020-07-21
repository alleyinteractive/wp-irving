<?php
/**
 * Video.
 *
 * Display a video using oembed markup.
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
		'config_callback' => function ( array $config ): array {

			/**
			 * If an aspect ratio value exists, check if it's an alias from the
			 * mapping (or not), and use as the wrapper's padding-bottom
			 * property. Values not aliased in the component map should be
			 * complete values with a percentage ex. "20%".
			 */
			$aspect_ratio_mapping = [
				'16:9'   => '56.25%',
				'1:1'    => '100%',
				'3:2'    => '66.67%',
				'4:3'    => '75%',
				'square' => '100%',
			];

			$aspect_ratio = $config['aspect_ratio'];

			// Update the style based on aspect ratio.
			if ( is_string( $aspect_ratio ) && ! empty( $aspect_ratio ) ) {
				$aspect_ratio                    = $aspect_ratio_mapping[ $aspect_ratio ] ?? $aspect_ratio; // Possibly use mapping.
				$updated_style                   = $config['style'] ?? []; // Get the current value of `style`.
				$updated_style['padding-bottom'] = $aspect_ratio; // Set the `padding-bottom` property.
			}

			return array_merge(
				$config,
				[
					'content' => wp_oembed_get( $config['video_url'] ),
					'style'   => $updated_style ?? $config['style'],
				]
			);
		},
	]
);
