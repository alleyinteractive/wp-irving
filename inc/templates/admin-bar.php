<?php
/**
 * Admin bar.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\Components\Component;
use WP_Irving\REST_API\Components_Endpoint;

/**
 * Automatically insert the admin bar component.
 *
 * @param array               $data     Data object to be hydrated by
 *                                      templates.
 * @param \WP_Query           $query    The current WP_Query object.
 * @param string              $context  The context for this request.
 * @param string              $path     The path for this request.
 * @param \WP_REST_Request    $request  WP_REST_Request object.
 * @param Components_Endpoint $endpoint Current class instance.
 * @return array The updated endpoint data.
 */
function setup_admin_bar(
	array $data,
	\WP_Query $query,
	string $context,
	string $path,
	\WP_REST_Request $request,
	Components_Endpoint $endpoint
): array {

	// Disable admin bar setup via filter.
	if ( ! apply_filters( 'wp_irving_setup_admin_bar', true ) ) {
		return $data;
	}

	// Unshift an admin bar component to the top of the `defaults` array.
	// This needs to be included in the `defaults` array regardless of whether
	// the user is logged in.
	if ( 'site' === $context ) {
		array_unshift(
			$data['defaults'],
			new Component( 'irving/wp-admin-bar' )
		);
	}

	// Only show the admin bar if logged in.
	if ( ! is_user_logged_in() ) {

		// Get and validate headers.
		$headers = [];
		if ( function_exists( 'getallheaders' ) ) {
			$headers = getallheaders();
		}

		if ( ! isset( $headers['Authorization'] ) ) {
			return $data;
		}

		array_unshift(
			$data['page'],
			new Component(
				'irving/wp-admin-bar',
				[
					'config'   => [
						'cookie_domain' => apply_filters(
							'wp_irving_auth_cookie_domain',
							COOKIE_DOMAIN
						),
					],
					'children' => [
						[
							'name'     => 'irving/container',
							'config'   => [
								'style' => [
									'text-align' => 'center',
									'padding'    => '1rem',
								],
							],
							'children' => [
								[
									'name'   => 'irving/text',
									'config' => [
										'content' => sprintf(
											'%1$s<a href="%3$s">%2$s</a>',
											esc_html__( 'Looks like your session has expired. ', 'wp-irving' ),
											esc_html__( 'Click here to generate a new token.', 'wp-irving' ),
											admin_url()
										),
										'html'    => true,
									],
								],
							],
						],
					],
				]
			)
		);

		return $data;
	}

	// Unshift an admin bar component to the top of the `page` array.
	array_unshift(
		$data['page'],
		new Component(
			'irving/wp-admin-bar',
			[
				'config' => [
					'iframe_src' => add_query_arg(
						$endpoint->custom_params,
						site_url( $path )
					),
				],
			]
		)
	);

	return $data;
}

/**
 * Callback for wp_head to add a base tag, CSS, and JS for the iframed content.
 */
function wp_head() {

	$referer = isset( $_SERVER['HTTP_REFERER'] )
		? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) )
		: '';

	// Bail early if the request did not come from the home URL.
	if (
		wp_parse_url( home_url(), PHP_URL_HOST )
		!== wp_parse_url( $referer, PHP_URL_HOST )
	) {
		return;
	}

	?>
	<base href="<?php echo esc_url( site_url( '/' ) ); ?>" target="_parent">
	<style>
		html {
			background-color: transparent;
		}

		body > * {
			visibility: hidden;
		}

		#wpadminbar {
			visibility: visible;
		}
	</style>
	<script type='text/javascript'>
		document.addEventListener('DOMContentLoaded', function() {
			var home = "<?php echo esc_url( home_url() ); ?>";

			// Get media query list using breakpoint where admin bar height changes.
			var mql = window.matchMedia('(min-width: 783px)');

			var sendHeightMessage = function(e) {
				top.postMessage({
					height: e.matches ? 32 : 46,
				}, home);
			};

			// Send the initial height and add listener to do so on every change.
			sendHeightMessage(mql);
			mql.addListener(sendHeightMessage);

			// Send the hover state.
			jQuery('#wpadminbar').hover(function() {
				top.postMessage({
					hovered: true,
				}, home);
			}, function() {
				top.postMessage({
					hovered: false,
				}, home);
			});

			var rest_endpoint = "<?php echo esc_url( site_url( '/wp-json/irving/v1/purge-cache' ) ); ?>";

			// Register the cache purge click listeners for front-end pages.
			jQuery('#wp-admin-bar-irving-cache-one').on('click', function() {
				const data = { 'action': 'irving_site_cache_purge' };
				jQuery.post(
					rest_endpoint,
					data,
					function(response) { alert(response.message); }
				).fail(function(error) { alert(error.responseJSON.message); });;
			});

			jQuery('#wp-admin-bar-irving-cache-two').on('click', function() {
				const data = {
					'action': 'irving_page_cache_purge',
					'route': window.location.href,
				};
				jQuery.post(
					rest_endpoint,
					data,
					function(response) { alert(response.message); }
				).fail(function(error) { alert(error.responseJSON.message); });
			});
		}, false);
	</script>
	<?php
}
add_action( 'wp_head', __NAMESPACE__ . '\wp_head' );

/**
 * Add `Irving` admin bar nodes. Provides shortcut links for the API, settings,
 * and documentation.
 *
 * @param \WP_Admin_Bar $admin_bar WP Admin Bar object.
 */
function add_admin_bar_nodes( \WP_Admin_Bar $admin_bar ) {

	if (
		! current_user_can(
			/**
			 * Capability required to see the Irving admin bar.
			 *
			 * @param string Required. Capability.
			 */
			apply_filters( 'wp_irving_api_link_cap', 'manage_options' )
		)
	) {
		return;
	}

	array_map(
		[ $admin_bar, 'add_node' ],
		array_merge(
			[
				// Base `Irving` node.
				[
					'id'    => 'irving',
					'title' => __( 'Irving Development', 'wp-irving' ),
					'href'  => home_url(),
				],
			],
			get_api_items(), // Global API links.
			get_admin_api_items(), // Admin-only API links.
			get_frontend_api_items(), // Frontend-only API links.
			get_setting_items(), // Irving settings.
			get_documentation_items(), // Documentation.
			get_cache_items(), // Cache busting.
		)
	);
}
add_action( 'admin_bar_menu', __NAMESPACE__ . '\add_admin_bar_nodes', 90 );

/**
 * Get an array of Irving api node args to be passed into
 * admin_bar->add_node().
 *
 * @return array
 */
function get_api_items(): array {
	return [
		[
			'href'   => Components_Endpoint::get_wp_irving_api_url( '/' ),
			'id'     => 'irving-api-links',
			'parent' => 'irving',
			'title'  => __( 'Components API', 'wp-irving' ),
		],
		[
			'href'   => Components_Endpoint::get_wp_irving_api_url( '/' ),
			'id'     => 'irving-homepage-api',
			'parent' => 'irving-api-links',
			'title'  => __( 'Homepage API', 'wp-irving' ),
		],
		[
			'href'   => rest_url( 'irving/v1/registered-components/' ),
			'id'     => 'irving-api-links-registered-components',
			'parent' => 'irving-api-links',
			'title'  => __( 'Registered Components API', 'wp-irving' ),
		],
	];
}

/**
 * Get an array of Irving api node args to be passed into
 * admin_bar->add_node().
 *
 * @return array
 */
function get_admin_api_items(): array {

	if ( ! is_admin() ) {
		return [];
	}

	$admin_api_items = [];

	/**
	 * Add a node for the post we're currently editing.
	 */
	$post_id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore
	if (
		'post' === ( get_current_screen()->base ?? '' )
		&& get_post( $post_id ) instanceof \WP_Post
	) {
		$admin_api_items[] = [
			'href'   => Components_Endpoint::get_wp_irving_api_url( get_the_permalink( $post_id ) ),
			'id'     => 'irving-api-links-current-post',
			'parent' => 'irving-api-links',
			'title'  => __( 'Post API', 'wp-irving' ),
		];
	}

	/**
	 * Add a node for the term we're currently editing.
	 */
	$term = get_term_by(
		'term_taxonomy_id',
		absint( $_GET['tag_ID'] ?? 0 ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		sanitize_text_field( wp_unslash( $_GET['taxonomy'] ?? '' ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	);
	if (
		'term' === ( get_current_screen()->base ?? '' )
		&& $term instanceof \WP_Term
	) {
		$admin_api_items[] = [
			'href'   => Components_Endpoint::get_wp_irving_api_url( get_term_link( $term->term_id ) ),
			'id'     => 'irving-api-links-current-term',
			'parent' => 'irving-api-links',
			'title'  => __( 'Term API', 'wp-irving' ),
		];
	}

	return $admin_api_items;
}

/**
 * Get an array of Irving frontend api node args to be passed into
 * admin_bar->add_node().
 *
 * @return array
 */
function get_frontend_api_items(): array {

	if ( is_admin() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return [];
	}

	$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

	// Current path where `context=page`.
	$frontent_api_items[] = [
		'href'   => Components_Endpoint::get_wp_irving_api_url( $request_uri, 'page' ),
		'id'     => 'irving-api-links-current-page',
		'parent' => 'irving-api-links',
		'title'  => __( 'Components API (context=page)', 'wp-irving' ),
	];

	// Current path where `context=site`.
	$frontent_api_items[] = [
		'href'   => Components_Endpoint::get_wp_irving_api_url( $request_uri, 'site' ),
		'id'     => 'irving-api-links-current-site',
		'parent' => 'irving-api-links',
		'title'  => __( 'Components API (context=site)', 'wp-irving' ),
	];

	return $frontent_api_items;
}

/**
 * Get an array of Irving setting node args to be passed into
 * admin_bar->add_node().
 *
 * @return array
 */
function get_setting_items(): array {
	return [
		[
			'id'     => 'irving-settings',
			'parent' => 'irving',
			'title'  => __( 'Settings', 'wp-irving' ),
		],
		[
			'href'   => admin_url( '/options-general.php?page=wp-irving-cache' ),
			'id'     => 'irving-settings-one',
			'parent' => 'irving-settings',
			'title'  => __( 'Caching', 'wp-irving' ),
		],
	];
}

/**
 * Get an array of Irving documentation node args to be passed into
 * admin_bar->add_node().
 *
 * @return array
 */
function get_documentation_items(): array {
	return [
		[
			'href'   => 'http://github.com/alleyinteractive/irving/wiki/',
			'id'     => 'irving-docs',
			'parent' => 'irving',
			'title'  => __( 'Documentation', 'wp-irving' ),
		],
		[
			'href'   => 'http://github.com/alleyinteractive/irving/wiki/',
			'id'     => 'irving-docs-irving-wiki',
			'parent' => 'irving-docs',
			'title'  => __( 'Irving Wiki', 'wp-irving' ),
		],
		[
			'href'   => 'http://github.com/alleyinteractive/irving/wiki/',
			'id'     => 'irving-docs-wp-irving-wiki',
			'parent' => 'irving-docs',
			'title'  => __( 'WordPress Plugin Wiki', 'wp-irving' ),
		],
		[
			'href'   => 'http://styled-components-storybook.irvingjs.com',
			'id'     => 'irving-docs-styled-components-storybook',
			'parent' => 'irving-docs',
			'title'  => __( 'Styled Components Storybook', 'wp-irving' ),
		],
	];
}

/**
 * Get an array of Irving cache busting node args to be passed into
 * admin_bar->add_node().
 *
 * @return array
 */
function get_cache_items(): array {
	$request_uri = '';
	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	}
	$redirect_url = is_admin()
		? site_url( $request_uri )
		: home_url( $request_uri );

	$arr = [
		[
			'id'    => 'irving-cache',
			'title' => __( 'Clear The Cache', 'wp-irving' ),
		],
		[
			'id'     => 'irving-cache-one',
			'parent' => 'irving-cache',
			'title'  => __( 'Clear Site Cache', 'wp-irving' ),
			'href'   => $redirect_url,
		],
	];

	$is_vip      = function_exists( 'wpcom_vip_purge_edge_cache_for_url' );
	$is_pantheon = function_exists( 'patheon_wp_clear_edge_paths' );
	// On VIP Go & pantheon environments, enable the option to purge the
	// URL specific page cache.
	if ( $is_vip || $is_pantheon ) {
		$arr[] = [
			'id'     => 'irving-cache-two',
			'parent' => 'irving-cache',
			'title'  => __( 'Clear Page Cache', 'wp-irving' ),
			'href'   => $redirect_url,
		];
	}

	return $arr;
}

/**
 * Register the cache purge click listeners for admin pages.
 */
function cache_purge_click_listener() {
	$rest_endpoint = site_url( '/wp-json/irving/v1/purge-cache' );
	?>
		<script type="text/javascript">
			jQuery('#wp-admin-bar-irving-cache-one').on('click', function() {
				const data = { 'action': 'irving_site_cache_purge' };
				jQuery.post(
					'<?php echo esc_url( $rest_endpoint ); ?>',
					data,
					function(response) { alert(response.message); }
				).fail(function(error) { alert(error.responseJSON.message); });;
			});
			jQuery('#wp-admin-bar-irving-cache-two').on('click', function() {
				const data = {
					'action': 'irving_page_cache_purge',
					'route': window.location.href,
				};
				jQuery.post(
					'<?php echo esc_url( $rest_endpoint ); ?>',
					data,
					function(response) { alert(response.message); }
				).fail(function(error) { alert(error.responseJSON.message); });
			});
		</script>
	<?php
}
add_action( 'admin_footer', __NAMESPACE__ . '\cache_purge_click_listener', 95 );
