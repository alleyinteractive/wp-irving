<?php
/**
 * Loader for components
 */

/**
 * Translation.
 */
add_filter(
	'wp_irving_component',
	function( $component ) {

		if ( ! isset( $component['__'] ) ) {
			return $component;
		}

		return __( $component['__'], 'wp-irving' );
	}
);

add_filter(
	'wp_irving_component_core/paragraph',
	function( $component ) {
		$component['name'] = 'irving/html';
		return $component;
	}
);


add_filter(
	'wp_irving_component',
	function( $component ) {
		if ( 0 === strpos( $component['name'] ?? '', 'bloginfo/' ) ) {
			return get_bloginfo( str_replace( 'bloginfo/', '', $component['name'] ) );
		}
		return $component;
	}
);

add_filter(
	'wp_irving_component_post/title',
	function( $component ) {
		$title = get_the_title();
		if ( ! empty( $title ) ) {
			return $title;
		}

		return __( 'Error: no global post context found', 'wp-irving' );
	}
);

add_filter(
	'wp_irving_component_post/content',
	function( $component ) {
		global $post;
		$component['children'] = \WP_Irving\Templates\convert_blocks_to_components( parse_blocks( $post->post_content ) );
		return $component;
	}
);
