<?php
/**
 * Admin bar.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

use WP_Irving\Component;

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

	// Only show the admin bar if logged in.
	if ( ! is_user_logged_in() ) {
		return $data;
	}

	// Unshift an admin bar component to the top of the `defaults` array.
	if ( 'site' === $context ) {
		array_unshift(
			$data['defaults'],
			new Component( 'irving/admin-bar' )
		);
	}

	// Unshift an admin bar component to the top of the `page` array.
	array_unshift(
		$data['page'],
		new Component( 'irving/admin-bar' )
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
