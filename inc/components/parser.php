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

/**
 * Gutenberg blocks.
 */
add_filter(
	'wp_irving_component_core/paragraph',
	function( $component ) {
		$component['name'] = 'irving/html';
		return $component;
	}
);

/**
 * Site.
 */

// Bloginfo.
add_filter(
	'wp_irving_component',
	function( $component ) {
		if ( 0 === strpos( $component['name'] ?? '', 'bloginfo/' ) ) {
			return get_bloginfo( str_replace( 'bloginfo/', '', $component['name'] ) );
		}
		return $component;
	}
);

// Menus
add_filter(
	'wp_irving_component_irving/menu',
	function( $component ) {

		$menu_items = (array) wp_get_nav_menu_items(
			get_nav_menu_locations()[ $component['config']['slug'] ?? '' ] ?? 0
		);

		$component['children'] = array_map(
			function( \WP_Post $menu_item_post ) {
				return [
					'name' => 'material/menu-item',
					'children' => [
						[
							'name' => 'material/link',
							'config' => [
								'href' => esc_url( $menu_item_post->url ?? get_the_permalink( $menu_item_post ) )
							],
							'children' => [
								esc_html( $menu_item_post->title ),
							],
						],
					],
				];
			},
			$menu_items
		);

		$component['name'] = 'irving/passthrough';

		return $component;
	}
);

/**
 * Posts.
 */
add_filter(
	'wp_irving_component_post/title',
	function( $component ) {
		$post_id = $component['config']['postId'] ?? get_the_ID();
		$title   = get_the_title( $post_id );
		if ( ! empty( $title ) ) {
			return html_entity_decode( $title );
		}

		return __( 'Error: no global post context found', 'wp-irving' );
	}
);

add_filter(
	'wp_irving_component_post/content',
	function( $component ) {
		global $post;
		$component['children'] = \WP_Irving\Templates\convert_blocks_to_components( parse_blocks( $post->post_content ) );
		$component['name'] = 'irving/container';
		$component['config']['themeName'] = 'fullBleed';
		return $component;
	}
);

add_filter(
	'wp_irving_component_post/excerpt',
	function( $component ) {
		return esc_html( substr( get_the_excerpt(), 30 ) );
	}
);

add_filter(
	'wp_irving_component_post/permalink',
	function( $component ) {
		$post_id = $component['config']['postId'] ?? get_the_ID();

		$component['config']['href'] = get_the_permalink( $post_id );
		$component['name']           = 'material/link';
		return $component;
	}
);

/**
 * Archives.
 */
add_filter(
	'wp_irving_component_irving/archive-title',
	function( $component ) {
		return get_the_archive_title();
	}
);

/**
 * Material UI.
 */
add_filter(
	'wp_irving_component_material/card-media',
	function( $component ) {
		$component['config']['url'] = get_the_post_thumbnail_url( $component['dataProvider']['postId'] ?? 0 );
		return $component;
	}
);

// add_filter(
// 	'wp_filter_component_irving/loop',
// 	function( $component ) {
// 		foreach ( $component['children'] as $child ) {
// 			$child['context'] = [
// 				'postId' => 1,
// 			];
// 		}
// 		return $component;
// 	}
// );

add_filter(
	'wp_irving_component_irving/loop',
	function( $component ) {

		$new_components     = [];
		$component_template = $component['children'];
		$loop_count         = absint( $component['config']['loop'] ?? 0 );

		$component['children'] = [];

		if ( 0 === $loop_count ) {
			while ( have_posts() ) {
				the_post();

				$loop_instance = $component_template;

				foreach ( $loop_instance as &$new_component ) {
					$new_component['dataProvider']['postId'] = get_the_ID();
				}

				$new_components = array_merge( $new_components, $loop_instance );
			}

			wp_reset_query();

			$component['children'] = $new_components;

			$component['name'] = 'irving/passthrough';
			return $component;
		}


		// for ( $x = 0; $x < $loop_count; $x++ ) {
		// 	$new_components[] = $component_template;
		// }

		$component['name'] = 'irving/passthrough';
		return $component;
	}
);

add_filter(
	'wp_irving_component_irving/next-post',
	function( $component ) {
		global $wp_query;

		$wp_query->the_post();

		$component['name'] = 'irving/passthrough';
	}
);

add_filter(
	'wp_irving_component_irving/wp_query',
	function( $component ) {

		// Execute a query. Probably allow-list some keys here.
		$query = new \WP_Query( $component['config'] );

		// Use the array of children components as a template.
		$component_template = $component['children'];

		$new_components = [];

		while ( $query->have_posts() ) {
			$query->the_post();

			$loop_instance = $component_template;

			foreach ( $loop_instance as &$new_component ) {
				$new_component['dataProvider']['postId'] = get_the_ID();
			}

			$new_components = array_merge( $new_components, $loop_instance );
		}

		wp_reset_query();

		$component['children'] = $new_components;

		$component['name'] = 'irving/passthrough';

		return $component;
	}
);
