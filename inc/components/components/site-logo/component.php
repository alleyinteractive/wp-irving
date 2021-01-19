<?php
/**
 * Logo.
 *
 * Display the site name or logo.
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

			// Set the site name.
			$config['site_name'] = html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, get_bloginfo( 'charset' ) );

			// If we have a logo, pass along the url.
			// @todo update this to use the image component.
			$custom_logo_id = get_theme_mod( 'custom_logo' );
			$logo_url       = wp_get_attachment_url( $custom_logo_id );

			if ( ! empty( $logo_url ) ) {
				$config['logo_image_url'] = $logo_url;
			}

			return $config;
		},
	]
);
