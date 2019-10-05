<?php
/**
 * Debugging.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Render the WP_Query in an HTML component for debugging.
 *
 * @param array            $data     Data for response.
 * @param \WP_Query        $wp_query WP_Query object corresponding to this
 *                                   request.
 * @param string           $context  The context for this request.
 * @param string           $path     The path for this request.
 * @param \WP_REST_Request $request  WP_REST_Request object.
 * @return  array Data for response.
 */
function render_query(
	array $data,
	\WP_Query $wp_query,
	string $context,
	string $path,
	\WP_REST_Request $request
): array {

	// Bail unless debugging is explicitly enabled.
	if ( true !== apply_filters( 'wp_irving_wp_query_debug', false ) ) {
		return $data;
	}

	// Build a default head and body.
	if ( 'site' === $context ) {
		$data['defaults'] = [
			[
				'name'     => 'head',
				'config'   => [],
				'children' => [
					[
						'name' => 'title',
						'config' => [],
						'children' => [
							get_bloginfo( 'name' ),
						],
					],
				],
			],
			[
				'name'     => 'body',
				'config'   => [],
				'children' => [],
			],
		];
	}

	ob_start();
	print_r( $wp_query );
	$debug = ob_get_clean();

	// Build page.
	$data['page'] = [
		[
			'name'     => 'body',
			'config'   => [],
			'children' => [
				[
					'name' => 'html',
					'config' => [
						'content' => sprintf(
							'<pre>%s</pre>',
							$debug
						),
					],
					'children' => [],
				],
			],
		],
	];

	return $data;
}
add_filter( 'wp_irving_components_route', __NAMESPACE__ . '\render_query', 100, 5 );

/**
 * Add a welcome message if nothing has been modified in previous hooks.
 *
 * @param array            $data     Data for response.
 * @param \WP_Query        $wp_query WP_Query object corresponding to this
 *                                   request.
 * @param string           $context  The context for this request.
 * @param string           $path     The path for this request.
 * @param \WP_REST_Request $request  WP_REST_Request object.
 * @return  array Data for response.
 */
function add_welcome_message(
	array $data,
	\WP_Query $wp_query,
	string $context,
	string $path,
	\WP_REST_Request $request
): array {

	if (
		empty( $data['defaults'] )
		&& empty( $data['page'] )
		&& empty( $data['providers'] )
		&& '' === $data['redirectTo']
		&& 0 === $data['redirectStatus']
	) {

		// Build defaults.
		if ( 'site' === $context ) {
			$data['defaults'] = [
				[
					'name'     => 'body',
					'config'   => [],
					'children' => [],
				],
			];
		}

		// Build page.
		$data['page'] = [
			[
				'name'     => 'body',
				'config'   => [],
				'children' => [
					[
						'name' => 'html',
						'config' => [
							'content' => sprintf(
								'<h2>%s</h2><p>%s</p>',
								__( 'Welcome to WP-Irving!', 'wp-irving' ),
								__( "If you're unsure how to get started, check out the wiki on GitHub.", 'wp-irving' )
							),
						],
						'children' => [],
					],
				],
			],
		];
	}

	return $data;
}
add_filter( 'wp_irving_components_route', __NAMESPACE__ . '\add_welcome_message', 1000, 5 );
