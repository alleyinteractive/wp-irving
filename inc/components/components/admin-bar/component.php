<?php
/**
 * Admin bar.
 *
 * WordPress admin bar.
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
		'children_callback' => function( array $children, array $config ): array {

			// Get the context of the head.
			$context = $config['context'] ?? '';

			// echo \WP_Irving\Templates\get_admin_bar_markup(); die();

			$children[] = new Component(
				'irving/text',
				[
					'config' => [
						'html' => true,
						'content' => ( 'site' === $context ) ?
							'Placeholder for admin bar' :
							html_entity_decode( \WP_Irving\Templates\get_admin_bar_markup() ) . 'hello world',
					],
				]
			);

			return $children;
		},
	]
);


add_action(
	'wp_irving_component_children',
		function( array $children, array $config, string $name ): array {
		// Ony run this action on the `irving/head` in a `page` context.
		if (
			'irving/head' !== $name
			|| 'page' !== ( $config['context'] ?? 'page' )
		) {
			return $children;
		}

		$children[] = new Component(
			'script',
			[
				'config' => [
					'type' => 'text/javascript',
					'src'  => 'https://jfc.alley.test/defector/wp-includes/js/admin-bar.min.js?ver=5.5.1',
					'id'   => 'admin-bar-js',
				],
			]
		);

		$children[] = new Component(
			'script',
			[
				'config' => [
					'type' => 'text/javascript',
					'src'  => 'https://jfc.alley.test/defector/wp-includes/js/jquery/jquery.js?ver=1.12.4-wp',
					'id'   => 'jquery-core-js',
				],
			]
		);

		$children[] = new Component(
			'link',
			[
				'config' => [
					'rel'   => 'stylesheet',
					'id'    => 'dashicons-css',
					'href'  => 'https://jfc.alley.test/defector/wp-includes/css/dashicons.min.css?ver=5.5.1',
					'type'  => 'text/css',
					'media' => 'all',
				],
			]
		);
		$children[] = new Component(
			'link',
			[
				'config' => [
					'rel'   => 'stylesheet',
					'id'    => 'admin-bar-css',
					'href'  => 'https://jfc.alley.test/defector/wp-includes/css/admin-bar.min.css?ver=5.5.1',
					'type'  => 'text/css',
					'media' => 'all',
				],
			]
		);

		return $children;
	},
	10,
	3
);
