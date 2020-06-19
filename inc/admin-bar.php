<?php
/**
 * Admin bar.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\Component;
use WP_Irving\REST_API\Components_Endpoint;

/**
 * Automatically insert the admin bar component.
 *
 * @param array            $data    Data object to be hydrated by templates.
 * @param \WP_Query        $query   The current WP_Query object.
 * @param string           $context The context for this request.
 * @param string           $path    The path for this request.
 * @param \WP_REST_Request $request WP_REST_Request object.
 * @return array The updated endpoint data.
 */
function setup_admin_bar(
	array $data,
	\WP_Query $query,
	string $context,
	string $path,
	\WP_REST_Request $request
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
			new Component( 'irving/admin-bar' )
		);
	}

	// Only show the admin bar if logged in.
	if ( ! is_user_logged_in() ) {
		return $data;
	}

	// Unshift an admin bar component to the top of the `page` array.
	array_unshift(
		$data['page'],
		new Component(
			'irving/admin-bar',
			[
				'config' => [
					'iframe_src' => site_url( $path ),
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
		}, false);
	</script>
	<?php
}
add_action( 'wp_head', __NAMESPACE__ . '\wp_head' );

/**
 * Add api link node to the admin bar from post edit screens.
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

	// Loops through an array of groups and/or nodes and registers them to the
	// admin bar.
	array_map(
		function( $item ) use ( $admin_bar ) {

			// Use `add_node()` by default.
			$admin_bar_method = $item['method'] ?? 'add_node';

			if ( method_exists( $admin_bar, $admin_bar_method ) ) {
				call_user_func( [ $admin_bar, $admin_bar_method ], $item );
			}
		},
		get_irving_admin_bar_items()
	);
}
add_action( 'admin_bar_menu', __NAMESPACE__ . '\add_admin_bar_nodes', 90 );

/**
 * Helper method to get all the nodes.
 *
 * @return array
 */
function get_irving_admin_bar_items(): array {

	$base_node = [
		[
			'id'    => 'irving',
			'title' => __( 'Irving Development', 'wp-irving' ),
			'href'  => home_url(),
		],
	];

	$api_links = [
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
			'href'   => add_query_arg(
				'path',
				'/',
				rest_url( 'irving/v1/registered-components/' )
			),
			'id'     => 'irving-api-links-registered-components',
			'parent' => 'irving-api-links',
			'title'  => __( 'Registered Components API', 'wp-irving' ),
		],
	];

	/**
	 * Add nodes to the frontend specifically.
	 */
	if ( ! is_admin() ) {

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

			// Current path where `context=page`.
			$api_links[] = [
				'href'   => Components_Endpoint::get_wp_irving_api_url( $request_uri, 'page' ),
				'id'     => 'irving-api-links-current-page',
				'parent' => 'irving-api-links',
				'title'  => __( 'Components API (context=page)', 'wp-irving' ),
			];

			// Current path where `context=site`.
			$api_links[] = [
				'href'   => Components_Endpoint::get_wp_irving_api_url( $request_uri, 'site' ),
				'id'     => 'irving-api-links-current-site',
				'parent' => 'irving-api-links',
				'title'  => __( 'Components API (context=site)', 'wp-irving' ),
			];
		}
	} else {

		// Add a node for the post we're currently editing.
		$post_id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore
		if (
			'post' === ( get_current_screen()->base ?? '' )
			&& get_post( $post_id ) instanceof \WP_Post
		) {
			$api_links[] = [
				'href'   => Components_Endpoint::get_wp_irving_api_url( get_the_permalink( $post_id ) ),
				'id'     => 'irving-api-links-current-post',
				'parent' => 'irving-api-links',
				'title'  => __( 'Post API', 'wp-irving' ),
			];
		}

		// Add a node for the term we're currently editing.
		$term = get_term_by(
			'term_taxonomy_id',
			absint( $_GET['tag_ID'] ?? 0 ), // phpcs:disable WordPress.Security.NonceVerification.Recommended
			sanitize_text_field( wp_unslash( $_GET['taxonomy'] ?? '' ) ) // phpcs:disable WordPress.Security.NonceVerification.Recommended
		);
		if (
			'term' === ( get_current_screen()->base ?? '' )
			&& $term instanceof \WP_Term
		) {
			$api_links[] = [
				'href'   => Components_Endpoint::get_wp_irving_api_url( get_term_link( $term->term_id ) ),
				'id'     => 'irving-api-links-current-term',
				'parent' => 'irving-api-links',
				'title'  => __( 'Term API', 'wp-irving' ),
			];
		}
	}

	$settings = [
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

	$docs = [
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

	// Put all the pieces together so it's easier to read and modify above.
	return array_merge( $base_node, $api_links, $settings, $docs );
}
