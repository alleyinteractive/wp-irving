<?php
/**
 * Site logo.
 *
 * @package Irving_Components
 */

if ( ! function_exists( '\WP_Irving\get_registry' ) ) {
	return;
}

/**
 * Register the component and callback.
 */
WP_Irving\get_registry()->register_component_from_config(
	__DIR__ . '/component',
	[
		'callback' => function( $component ) {

			// Create a new instance of the core irving/logo component.
			$logo = new WP_Irving\Component(
				'irving/logo',
				[
					'config' => [
						'site_name' => get_bloginfo( 'name' ),
					],
				]
			);

			// Get the custom logo.
			$custom_logo_id = get_theme_mod( 'custom_logo' );
			$logo_url       = wp_get_attachment_url( $custom_logo_id );
			if ( ! empty( $logo_url ) ) {
				$logo->set_config( 'logo_url', $logo_url );
			}

			// Set the child and clear the name so site/logo uses a fragment.
			$component->set_child( $logo );
			$component->set_name( '' );

			return $component;
		},
	]
);
