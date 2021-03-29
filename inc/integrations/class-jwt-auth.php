<?php
/**
 * JWT Authentication Integration.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;

/**
 * Singleton class for creating a cross-domain cookie with a JSON Web Token
 * that Irving core can read and use for Component API authentication.
 */
class JWT_Auth {
	use Singleton;

	/**
	 * Cookie name that Irving core expects.
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'authorizationBearerToken';

	/**
	 * Name for the keypair.
	 *
	 * @var string
	 */
	const KEYPAIR_NAME = 'wp-irving-jwt-auth';

	/**
	 * Setup the singleton. Validate JWT is installed, and setup hooks.
	 */
	public function setup() {

		// Validate that JWT exists and is enabled.
		if ( ! defined( 'JWT_AUTH_VERSION' ) ) {
			return;
		}

		// Set or unset the cookie upon init.
		add_action( 'init', [ $this, 'handle_cookie' ] );

		// Return the API result instead of an invalid token error. This
		// ensures invalid tokens don't error out, and instead get the
		// non-authenticated components API.
		add_filter(
			'rest_authentication_invalid_token',
			function( $token, $result ) {
				return $result;
			},
			10,
			2
		);
	}

	/**
	 * Handle the cookie logic upon init.
	 */
	public function handle_cookie() {

		/**
		 * Determine the cross domain cookie domain.
		 *
		 * @todo Can we swap this for the COOKIE_DOMAIN constant at somepoint?
		 *
		 * @var string
		 */
		$this->cookie_domain = apply_filters(
			'wp_irving_jwt_token_cookie_domain',
			wp_parse_url( home_url(), PHP_URL_HOST )
		);

		$this->possibly_set_cookie();
		$this->possibly_remove_cookie();
	}

	/**
	 * Get a clean token and set the cookie.
	 *
	 * @todo Display an admin error message if the token and/or cookie wasn't
	 *       set correctly.
	 *
	 * @return bool Was the cookie set successfully?
	 */
	public function possibly_set_cookie(): bool {

		// We've already set the cookie.
		if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) ) { // phpcs:ignore
			return false;
		}

		// Get and/or create a token to store in the cookie.
		$app_pass = $this->get_or_create_application_password();

		// Invalid response. This needs better error handing.
		if ( empty( $app_pass ) ) {
			return false;
		}

		// Set a cross domain cookie using the JWT.
		// phpcs:ignore
		setcookie(
			self::COOKIE_NAME,
			$app_pass,
			time() + ( DAY_IN_SECONDS * 7 ) - MINUTE_IN_SECONDS, // Expire the cookie one minute before the token does.
			'/',
			$this->cookie_domain,
			true,
			false
		);

		return true;
	}

	/**
	 * If the user isn't logged in. but has an auth cookie, kill it.
	 *
	 * @return bool Was the cookie was removed successfully?
	 */
	public function possibly_remove_cookie(): bool {

		// Only care if we have a cookie.
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) { // phpcs:ignore
			return false;
		}

		// Get token from cookie.
		$app_pass = $_COOKIE[ self::COOKIE_NAME ]; // phpcs:ignore

		// // Decode the token.
		// $jwt = $wp_rest_token->decode_token( $token );
		// if ( is_wp_error( $jwt ) ) {
		// 	$this->remove_cookie();
		// 	return true;
		// }

		// // Determine if the token issuer is valid.
		// $issuer_valid = $wp_rest_token->validate_issuer( $jwt->iss );
		// if ( is_wp_error( $issuer_valid ) ) {
		// 	$this->remove_cookie();
		// 	return true;
		// }

		// // Determine if the token user is valid.
		// $user_valid = $wp_rest_token->validate_user( $jwt );
		// if ( is_wp_error( $user_valid ) ) {
		// 	$this->remove_cookie();
		// 	return true;
		// }

		return false;
	}

	/**
	 * Set the expiration on the cookie to unset it.
	 */
	public function remove_cookie() {
		// phpcs:ignore
		setcookie(
			self::COOKIE_NAME,
			null,
			-1,
			'/',
			$this->cookie_domain
		);
	}

	/**
	 * Get or create an application password.
	 *
	 * @return string
	 */
	public function get_or_create_application_password() : string {
		$user_id    = get_current_user_id();
		$app_name   = get_bloginfo( 'name' ) . ' Irving App, user ' . $user_id;

		// Set the new request with the new key and secret.
		$app_pass_request = new \WP_REST_Request(
			\WP_REST_Server::CREATABLE,
			'/wp/v2/users/me/application-passwords'
		);
		$app_pass_request->set_query_params(
			[
				'name'   => $app_name,
				'app_id' => wp_generate_uuid4(),
			]
		);

		// Let's get the token.
		$result = rest_do_request( $app_pass_request );
		$status = $result->status ?? 0;

		if ( 201 === $status || 200 === $status && ! empty( $result->data ) ) {
			return str_replace( ' ', '', $result->data['password'] );
		}

		return '';
	}
}
