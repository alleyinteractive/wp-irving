<?php
/**
 * WP Core Application Passwords Authentication Integration.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;
use WP_Application_Passwords;

// phpcs:ignoreFile WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
/**
 * Singleton class for creating a cross-domain cookie with an application password
 * that Irving core can read and use for Component API authentication.
 */
class Application_Passwords_Auth {
	use Singleton;

	/**
	 * Name of the application.
	 *
	 * @var string
	 */
	const APP_NAME = 'Irving Frontend Application';

	/**
	 * Cookie name that Irving core expects for auth token.
	 *
	 * @var string
	 */
	const TOKEN_COOKIE_NAME = 'authorizationBasicToken';

	/**
	 * Cookie name for app ID.
	 *
	 * @var string
	 */
	const APP_ID_COOKIE_NAME = 'authorizationAppID';

	/**
	 * Cookie name for the flag which should trigger a new token to be created.
	 *
	 * @var string
	 */
	const RESET_TOKEN_FLAG_COOKIE_NAME = 'irvingResetToken';

	/**
	 * Cookie domain for authorization cookies.
	 *
	 * @var string
	 */
	public $cookie_domain = '';

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

		// Ensure auth errors fail silently, as to not break the Irving frontend.
		add_filter( 'rest_authentication_errors', [ $this, 'handle_authentication_errors' ], 99 );

		// Add a shortcut to the Tools menu for refreshing the token.
		add_action( 'admin_menu', [ $this, 'add_tools_link' ] );
	}

	/**
	 * Short-circuit authentication errors and allow Irving to return unauthenticated components data.
	 *
	 * @param array $errors Authentication errors.
	 */
	public function handle_authentication_errors( $errors ) {
		if ( strpos( $_SERVER['REQUEST_URI'], \WP_Irving\REST_API\Endpoint::get_namespace() ) ) {
			return null;
		}

		return $errors;
	}

	/**
	 * Handle the cookie logic upon init.
	 */
	public function handle_cookie() {
		// Prevent Favicon requests from affecting cookies.
		if ( isset( $_SERVER['REQUEST_URI'] ) && '/favicon.ico' === $_SERVER['REQUEST_URI'] ) {
			return;
		}

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

		$this->possibly_clear_all_auth();
		$this->possibly_set_cookie();
		$this->possibly_remove_cookie();
		$this->possibly_remove_bearer_cookie();
	}

	/**
	 * Reset all cookies and tokens if we have a reset flag.
	 */
	public function possibly_clear_all_auth() {
		if (
			! isset( $_COOKIE[ self::RESET_TOKEN_FLAG_COOKIE_NAME ] ) // phpcs:ignore
			&& ! isset( $_GET['refresh-irving-token'] )
		) {
			return;
		}

		$this->remove_cookie();
		$this->delete_irving_application_passwords();

		add_action(
			'admin_notices',
			function() {
				printf(
					'<div class="notice notice-success is-dismissible"><p>%1$s</p></div>',
					esc_html__( 'Your login session has been renewed.', 'wp-irving' )
				);
			}
		);
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
		$this->delete_irving_application_passwords();
		$app_pass_data = $this->create_application_password();

		// Invalid response. This needs better error handing.
		if ( empty( $app_pass_data ) ) {
			return false;
		}

		// Set a cross domain cookie using the application password.
		setcookie(
			self::TOKEN_COOKIE_NAME,
			$this->get_formatted_token_cookie( $app_pass_data['password'] ),
			time() + ( DAY_IN_SECONDS * 7 ),
			'/',
			$this->cookie_domain,
			true,
			false
		);

		setcookie(
			self::APP_ID_COOKIE_NAME,
			$app_pass_data['app_id'],
			time() + ( DAY_IN_SECONDS * 7 ),
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

		return base64_encode( $user->data->user_login . ':' . $password );
	}

	/**
	 * If the user isn't logged in, but has an auth cookie, kill it.
	 *
	 * @todo what other checks do we need in here that would trigger removing the cookie? Last used?
	 * @return bool Was the cookie was removed successfully?
	 */
	public function possibly_remove_cookie(): bool {
		// Only care if we have a cookie.
		if ( ! isset( $_COOKIE[ self::TOKEN_COOKIE_NAME ] ) ) { // phpcs:ignore
			return false;
		}

		// Get active application passwords.
		$app_passwords = WP_Application_Passwords::get_user_application_passwords( get_current_user_id() );

		// If no passwords are stored for current user, remove cookies.
		if ( empty( $app_passwords ) ) {
			$this->remove_cookie();
		}

		$matching_passwords = array_filter(
			$app_passwords,
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

		setcookie(
			self::RESET_TOKEN_FLAG_COOKIE_NAME,
			null,
			-1,
			'/',
			$this->cookie_domain
		);
	}

	/**
	 * Remove any lingering bearer token cookie.
	 */
	public function possibly_remove_bearer_cookie() {
		if ( isset( $_COOKIE[ JWT_Auth::TOKEN_COOKIE_NAME ] ) ) {
			setcookie(
				JWT_Auth::TOKEN_COOKIE_NAME,
				null,
				-1,
				'/',
				$this->cookie_domain
			);
		}
	}

	/**
	 * Delete all old application passwords for the current user.
	 *
	 * @return void
	 */
	public function delete_irving_application_passwords() {
		$user_id       = get_current_user_id();
		$app_passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );

		// Loop through all passwords for this user, and delete any with a
		// matching name.
		foreach ( $app_passwords as $password ) {
			if ( self::APP_NAME === $password['name'] ) {
				 WP_Application_Passwords::delete_application_password( $user_id, $password['uuid'] );
			}
		}
	}

	/**
	 * Get or create an application password.
	 *
	 * @return array
	 */
	public function create_application_password() : array {
		$user_id  = get_current_user_id();

		// Set the new request with the new key and secret.
		$app_pass_data = WP_Application_Passwords::create_new_application_password(
			$user_id,
			[
				'name'   => self::APP_NAME,
				'app_id' => wp_generate_uuid4(),
			]
		);

		if ( is_wp_error( $app_pass_data ) || empty( $app_pass_data[0] ) || empty( $app_pass_data[1] ) ) {
			return [];
		}

		return [
			'password' => $app_pass_data[0],
			'app_id'   => $app_pass_data[1]['app_id'] ?? '',
		];
	}

	/**
	 * Add an admin bar shortcut to refresh the token.
	 */
	public function add_tools_link() {
		add_submenu_page(
			'tools.php',
			__( 'Generate New Authentication Token', 'wp-irving' ),
			__( 'Generate New Authentication Token', 'wp-irving' ),
			'edit_posts',
			add_query_arg( 'refresh-irving-token', true, admin_url() ),
			null,
			10
		);
	}
}
