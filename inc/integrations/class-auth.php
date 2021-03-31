<?php
/**
 * WP Core Application Passwords Authentication Integration.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;

/**
 * Singleton class for creating a cross-domain cookie with an application password
 * that Irving core can read and use for Component API authentication.
 */
class Auth {
	use Singleton;

	/**
	 * Cookie name that Irving core expects for auth token.
	 *
	 * @var string
	 */
	const TOKEN_COOKIE_NAME = 'authorizationToken';

	/**
	 * Cookie name for app ID.
	 *
	 * @var string
	 */
	const APP_ID_COOKIE_NAME = 'authorizationAppID';


	/**
	 * Setup the singleton. Validate Application Passswords are availble, and setup hooks.
	 */
	public function setup() {
		// Validate we have access to core application passwords.
		if ( ! class_exists( '\WP_Application_Passwords' ) ) {
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
		 * @todo Can/should we rename this filter without `jwt`?
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
		if ( isset( $_COOKIE[ self::TOKEN_COOKIE_NAME ] ) ) { // phpcs:ignore
			return false;
		}

		// Refresh application password if cookie has expired or has been removed.
		$this->delete_all_application_passwords();
		$app_pass = $this->create_application_password();

		// Invalid response. This needs better error handing.
		if ( empty( $app_pass ) ) {
			return false;
		}

		// Set a cross domain cookie using the application password.
		// phpcs:ignore
		setcookie(
			self::TOKEN_COOKIE_NAME,
			$this->get_formatted_token_cookie( $app_pass['password'] ),
			time() + ( DAY_IN_SECONDS * 7 ) - MINUTE_IN_SECONDS, // Expire the cookie one minute before the token does.
			'/',
			$this->cookie_domain,
			true,
			false
		);

		setcookie(
			self::APP_ID_COOKIE_NAME,
			$app_pass['app_id'],
			time() + ( DAY_IN_SECONDS * 7 ) - MINUTE_IN_SECONDS, // Expire the cookie one minute before the token does.
			'/',
			$this->cookie_domain,
			true,
			false
		);

		return true;
	}

	/**
	 * Format cookie and prepare it to be used in app requests.
	 *
	 * @param string $password Application password.
	 * @return string
	 */
	public function get_formatted_token_cookie( $password ) : string {
		$user = wp_get_current_user();

		if ( is_wp_error( $user ) ) {
			return '';
		}

		return base64_encode( $user->data->user_login . ':' . str_replace( ' ', '', $password ) );
	}

	/**
	 * If the user isn't logged in. but has an auth cookie, kill it.
	 *
	 * @todo what other checks do we need in here that would trigger removing the cookie?
	 * @return bool Was the cookie was removed successfully?
	 */
	public function possibly_remove_cookie(): bool {
		// Only care if we have a cookie.
		if ( ! isset( $_COOKIE[ self::TOKEN_COOKIE_NAME ] ) ) { // phpcs:ignore
			return false;
		}

		// Get active application passwords.
		$passwords = \get_user_meta(
			get_current_user_id(),
			\WP_Application_Passwords::USERMETA_KEY_APPLICATION_PASSWORDS,
			true
		);

		// If no passwords are stored for current user, remove cookies.
		if ( empty( $passwords ) ) {
			$this->remove_cookie();
		}

		// phpcs:ignore
		$matching_passwords = array_filter(
			$passwords,
			function ( $password ) {
				return (
					! empty( $_COOKIE[ self::APP_ID_COOKIE_NAME ] ) &&
					$password['app_id'] == $_COOKIE[ self::APP_ID_COOKIE_NAME ]
				);
			}
		);

		// If no stored passwords match the curren App ID, remove cookies.
		if ( empty( $matching_passwords ) ) {
			$this->remove_cookie();
		}

		return false;
	}

	/**
	 * Set the expiration on the cookie to unset it.
	 */
	public function remove_cookie() {
		// phpcs:ignore
		setcookie(
			self::TOKEN_COOKIE_NAME,
			null,
			-1,
			'/',
			$this->cookie_domain
		);

		setcookie(
			self::APP_ID_COOKIE_NAME,
			null,
			-1,
			'/',
			$this->cookie_domain
		);
	}

	/**
	 * Delete all old application passwords for the current user.
	 *
	 * @return void
	 */
	public function delete_all_application_passwords() {
		// Set the new request with the new key and secret.
		$app_pass_delete_request = new \WP_REST_Request(
			\WP_REST_Server::DELETABLE,
			'/wp/v2/users/me/application-passwords'
		);

		// Let's get the token.
		rest_do_request( $app_pass_delete_request );
	}

	/**
	 * Get or create an application password.
	 *
	 * @return array
	 */
	public function create_application_password() : array {
		$user     = wp_get_current_user();
		$app_name = get_bloginfo( 'name' ) . ' Irving App, user ' . $user->ID;

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
			return $result->data;
		}

		return [];
	}
}
