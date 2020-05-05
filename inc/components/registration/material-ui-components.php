<?php
/**
 * Registration for Material UI commponents.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

/**
 * Add the post thumbnail to a card.
 *
 * @return array
 */
register_component(
	'material/card-media',
	[
		'callback' => function( $component ) {

			if ( ! empty( $component['config']['image'] ?? '' ) ) {
				return $component;
			}

			$component['config']['image'] = get_the_post_thumbnail_url( $component['data_provider']['postId'] ?? get_the_ID() );
			return $component;
		},
	]
);

/**
 * Material UI menu, leveraging WordPress' menu API.
 *
 * @param string $comopnent['config']['slug'] Slug of the location of the menu.
 * @return arrray
 */
register_component(
	'irving/menu',
	[
		'callback' => function( $component ) {

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
									'href' => esc_url( $menu_item_post->url ?? get_the_permalink( $menu_item_post ) ),
								],
								'children' => [
									esc_html( $menu_item_post->title ),
								],
							],
						],
					];
				},
				array_filter( $menu_items ?? [] )
			);

			$component['name'] = '';

			return $component;
		},
	]
);

/**
 * Setup a Material UI card for the featured image and caption.
 *
 * @return array
 */
register_component(
	'irving/featured-media',
	[
		'callback' => function( $component ) {

			$post_id = $component['data_provider']['postId'] ?? get_the_ID();

			// Get and validate image url.
			$image_url = get_the_post_thumbnail_url( $post_id );
			if ( empty( $image_url ) ) {
				$component['name'] = ''; // Don't render anything.
				return $component;
			}

			$component = [
				'name'     => 'material/card-content',
				'config'   => [
					'gutterBottom' => true,
				],
				'children' => [
					[
						'name'   => 'material/card-media',
						'config' => [
							'image' => $image_url,
							'style' => [
								'height' => '450px',
							],
						],
					],
				],
			];

			$caption = wp_get_attachment_caption( get_post_thumbnail_id( $post_id ) );
			if ( ! empty( $caption ) ) {
				$component['children'][] = [
					'name'     => 'material/typography',
					'config'   => [
						'color'     => 'textSecondary',
						'variant'   => 'body2',
						'component' => 'p',
					],
					'children' => [
						$caption,
					],
				];
			}

			return $component;
		},
	]
);
