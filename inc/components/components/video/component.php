<?php
/**
 * Video.
 *
 * Display a video using oembed markup.
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

			$aspect_ratio = $component->get_config( 'aspect_ratio' );
			if ( is_string( $aspect_ratio ) && ! empty( $aspect_ratio ) ) {
				$aspect_ratio                    = $aspect_ratio_mapping[ $aspect_ratio ] ?? $aspect_ratio; // Possibly use mapping.
				$updated_style                   = $component->get_config( 'style' ); // Get the current value of `style`.
				$updated_style['padding-bottom'] = $aspect_ratio; // Set the `padding-bottom` property.
				$component->set_config( 'style', $updated_style ); // Save.
			}

			return $component
				->set_config( 'content', wp_oembed_get( $component->get_config( 'video_url' ) ) )
				->set_config( 'oembed', true )
				->set_config( 'tag', 'div' )
				->set_theme( 'responsive-embed' );
		},
	]
);
