<?php
/**
 * Customizer.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Customize_Manager;
use WP_Customize_Media_Control;

/**
 * Support for a fallback image that integrates with the
 * `irving/post-featured-image` component.
 *
 * @param WP_Customize_Manager $wp_customize Cutomize object.
 */
function add_theme_fallback_image( WP_Customize_Manager $wp_customize ) {

	// Add a new setting for the fallback image.
	$wp_customize->add_setting(
		'wp_irving-fallback_image',
		[
			'default'    => '',
			'capability' => 'edit_theme_options',
			'type'       => 'theme_mod',
		]
	);

	// Add the image control.
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'wp_irving_fallback_image',
			[
				'label'    => __( 'Fallback Image', 'wp-irving' ),
				'section'  => 'title_tagline',
				'settings' => 'wp_irving-fallback_image',
				'priority' => 80,
			]
		)
	);
}
add_action( 'customize_register', __NAMESPACE__ . '\add_theme_fallback_image' );
