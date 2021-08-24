<?php
/**
 * WP Core Application Passwords Authentication Integration.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Error;
use WP_Irving\REST_API\Endpoint;
use WP_Irving\Singleton;
use WP_Application_Passwords;
use WP_User;

// phpcs:ignoreFile WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
/**
 * Singleton class for creating a cross-domain cookie with an application password
 * that Irving core can read and use for Component API authentication.
 */
class Application_Passwords_Auth {
	use Singleton;

	/**
	 * Application ID for WP Irving. This was randomly generated.
	 */
	const APP_ID = '1481e1b4-8e40-44ed-9b6d-92ba5066ab20';

	/**
	 * Cookie name which Irving expects for auth token.
	 *
	 * @var string
	 */
	const TOKEN_COOKIE_NAME = 'authorizationBasicToken';

	/**
	 * Cookie name which Irving expects for Application Password UUID.
	 *
	 * @var string
	 */
	const UUID_COOKIE_NAME = 'authorizationAppID';

	/**
	 * Cookie domain for authorization cookies.
	 *
	 * @var string
	 */
	public $cookie_domain = '';

	/**
	 * Application name for application password tokens.
	 *
	 * @var string
	 */
	protected $app_name;

	/**
	 * Cookie TTL.
	 *
	 * @var int
	 */
	protected $ttl;

	/**
	 * Token for the current session.
	 *
	 * This is stored in the instance because it might come from an action or
	 * from `wp_get_session_token()` as necessary.
	 *
	 * @var string
	 */
	protected $session_token;

	/**
	 * If a new application password is generated, keep it in memory.
	 *
	 * @var array
	 */
	protected $new_application_password;

	/**
	 * Set up the singleton. Validate Application Passwords are available, and
	 * setup hooks.
	 */
	public function setup() {
		// Validate we have access to core application passwords.
		if ( ! class_exists( '\WP_Application_Passwords' ) ) {
			return;
		}

		/**
		 * Filter the application name as it appears on application passwords.
		 *
		 * @param string $app_name Application name.
		 */
		$this->app_name = apply_filters(
			'wp_irving_application_passwords_name',
			'Irving Frontend Website Session'
		);

		/**
		 * Determine the cross domain cookie domain.
		 *
		 * @param string $cookie_domain Cookie domain for the auth token.
		 */
		$this->cookie_domain = apply_filters(
			'wp_irving_auth_cookie_domain',
			COOKIE_DOMAIN
		);

		/**
		 * Filters the TTL for the cookies. This is added to time().
		 *
		 * @param int  $length   Duration of the expiration period in seconds.
		 * @param int  $user_id  User ID.
		 * @param bool $remember Whether to remember the user login. Default false.
		 */
		$this->ttl = apply_filters( 'wp_irving_application_passwords_ttl', 14 * DAY_IN_SECONDS );

		// When WP sets the auth cookie, also set the app password cookies.
		add_action( 'set_auth_cookie', [ $this, 'set_cookies_with_session_cookies' ], 10, 6 );

		// When a user logs out, remove the app password and cookies.
		add_action( 'wp_logout', [ $this, 'destroy_application_password_on_logout' ] );

		// When WP fails authentication an app password, maybe remove cookies.
		add_action( 'application_password_failed_authentication', [ $this, 'clear_cookies_on_failed_authentication' ] );

		// Don't let Irving App Passwords older than the TTL be used.
		add_action( 'wp_authenticate_application_password_errors', [ $this, 'check_application_password_age_on_use' ], 10, 4 );

		// Ensure auth errors fail silently, as to not break the Irving frontend.
		add_filter( 'rest_authentication_errors', [ $this, 'handle_authentication_errors' ], 99 );
	}

	/**
	 * Short-circuit authentication errors and allow Irving to return
	 * unauthenticated components' data.
	 *
	 * @param \WP_Error|null|true $errors Authentication errors.
	 * @return \WP_Error|null|true
	 */
	public function handle_authentication_errors( $errors ) {
		if (
			null !== $errors
			&& strpos( $_SERVER['REQUEST_URI'], Endpoint::get_namespace() )
		) {
			return null;
		}

		return $errors;
	}

	/**
	 * Set Irving application password cookies when the WP auth cookie is set.
	 *
	 * Note that on login, the current user hasn't been set by the time this
	 * runs, so $user->ID will be empty.
	 *
	 * @param string $auth_cookie Authentication cookie value.
	 * @param int    $expire      The time the login grace period expires as a UNIX timestamp.
	 *                            Default is 12 hours past the cookie's expiration time.
	 * @param int    $expiration  The time when the authentication cookie expires as a UNIX timestamp.
	 *                            Default is 14 days from now.
	 * @param int    $user_id     User ID.
	 * @param string $scheme      Authentication scheme. Values include 'auth' or 'secure_auth'.
	 * @param string $token       User's session token to use for this cookie.
	 */
	public function set_cookies_with_session_cookies( $auth_cookie, $expire, $expiration, $user_id, $scheme, $token ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! empty( $user->ID ) ) {
			$this->session_token = $token;
			$this->orchestrate_cookies( $user->ID, $user->user_login, $expire !== 0 );
		}
	}

	/**
	 * On logout, destroy the application password and related cookies.
	 *
	 * @param int $user_id User ID.
	 */
	public function destroy_application_password_on_logout( $user_id ) {
		if ( isset( $_COOKIE[ self::UUID_COOKIE_NAME ] ) ) {
			WP_Application_Passwords::delete_application_password( $user_id, $_COOKIE[ self::UUID_COOKIE_NAME ] );
			$this->remove_cookies();
		}
	}

	/**
	 * If the application password in the cookie was used and authentication
	 * failed, remove the cookies.
	 */
	public function clear_cookies_on_failed_authentication() {
		if (
			isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_COOKIE[ self::TOKEN_COOKIE_NAME ] )
			&& $_COOKIE[ self::TOKEN_COOKIE_NAME ] === $this->get_formatted_token_cookie( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] )
		) {
			$this->remove_cookies();
		}
	}

	/**
	 * Don't allow Irving Application Passwords to be used longer than TTL.
	 *
	 * @param WP_Error $error    The error object.
	 * @param WP_User  $user     The user authenticating.
	 * @param array    $item     The details about the application password.
	 */
	public function check_application_password_age_on_use( $error, $user, $item ) {
		if (
			! empty( $item['name'] )
			&& $this->is_application_password_from_irving( $item )
			&& $this->is_application_password_expired( $item )
		) {
			$error->add(
				'irving_application_password_expired',
				__( 'The Irving application password is expired, please sign in again.', 'wp-irving' )
			);

			// Take this opportunity to remove old passwords.
			$this->prune_old_application_passwords( $user->ID );

			// Attempt to remove cookies.
			$this->remove_cookies();
		}

		return $error;
	}

	/**
	 * Orchestrate the cookie generation and setting process.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $user_login User login.
	 * @param bool   $remember   Should this be a session cookie or full TTL.
	 * @return bool
	 */
	public function orchestrate_cookies( int $user_id, string $user_login, bool $remember = true ): bool {
		$current_password = $this->get_current_session_application_password( $user_id );

		if ( empty( $current_password ) ) {
			// Get a new application password.
			$current_password = $this->create_application_password( $user_id, $remember );
			if ( empty( $current_password['unhashed_password'] ) ) {
				return false;
			}

			$token_value = $this->get_formatted_token_cookie(
				$user_login,
				$current_password['unhashed_password']
			);

			// Prune the old application passwords.
			$this->prune_old_application_passwords( $user_id );

			return $this->set_cookies( $token_value, $current_password['uuid'], $remember );
		}

		return false;
	}

	/**
	 * Remove old application passwords that were created more than TTL seconds
	 * ago.
	 *
	 * @param int $user_id User ID.
	 */
	public function prune_old_application_passwords( int $user_id ) {
		$this->delete_irving_application_passwords( $user_id );
	}

	/**
	 * Delete all old Irving application passwords for the current user.
	 *
	 * @param int       $user_id      User ID.
	 * @param bool|null $expired_only Optional. Should only expired tokens be
	 *                                deleted? Defaults to true.
	 */
	public function delete_irving_application_passwords( int $user_id, ?bool $expired_only = true ) {
		$app_passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );

		// Loop through all passwords for this user to find those with a matching name.
		foreach ( $app_passwords as $app_password ) {
			if ( $this->is_application_password_from_irving( $app_password ) ) {
				// Optionally check the created timestamp.
				if ( $expired_only && ! $this->is_application_password_expired( $app_password ) ) {
					continue;
				}

				// Delete the password.
				WP_Application_Passwords::delete_application_password( $user_id, $app_password['uuid'] );
			}
		}
	}

	/**
	 * Trigger the expiration on the cookies to unset them.
	 */
	public function remove_cookies() {
		setcookie( self::TOKEN_COOKIE_NAME, null, -1, COOKIEPATH, $this->cookie_domain );
		setcookie( self::UUID_COOKIE_NAME, null, -1, COOKIEPATH, $this->cookie_domain );
	}

	/**
	 * Get the application password array for the current session, if possible.
	 *
	 * @param int $user_id User ID
	 * @return array|false Array of application password data on success, false
	 *                     on failure.
	 */
	protected function get_current_session_application_password( int $user_id ) {
		// If an application password was just created, use that.
		if ( isset( $this->new_application_password ) ) {
			return $this->new_application_password;
		}

		if ( isset( $_COOKIE[ self::UUID_COOKIE_NAME ], $_COOKIE[ self::TOKEN_COOKIE_NAME ] ) ) {
			// If the cookie is set, ensure the value is valid.
			return $this->get_verified_application_password(
				$_COOKIE[ self::UUID_COOKIE_NAME ],
				$this->get_password_from_formatted_token( $_COOKIE[ self::TOKEN_COOKIE_NAME ] ),
				$user_id
			);
		}

		return false;
	}

	/**
	 * Get and verify an application password.
	 *
	 * @param string $uuid     Application password UUID.
	 * @param string $password Raw password for the Application Password.
	 * @param int    $user_id  User ID.
	 * @return array|false Application Password data on success, false on
	 *                     failure.
	 */
	protected function get_verified_application_password( string $uuid, string $password, int $user_id ) {
		// Get active application passwords.
		$app_password = WP_Application_Passwords::get_user_application_password( $user_id, $uuid );

		if (
			! empty( $app_password )
			&& wp_check_password( $password, $app_password['password'], $user_id )
		) {
			return $app_password;
		}

		return false;
	}

	/**
	 * Attempt to pull the raw application password from the formatted token.
	 *
	 * @param string $token Formatted token.
	 * @return string|false
	 */
	protected function get_password_from_formatted_token( string $token ) {
		$values = explode( ':', base64_decode( $token ) );
		return ! empty( $values[1] ) ? $values[1] : false;
	}

	/**
	 * Create an application password.
	 *
	 * @param int  $user_id  User ID.
	 * @param bool $remember Should this be a short-term or full TTL token.
	 * @return array
	 */
	protected function create_application_password( int $user_id, bool $remember = true ) : array {
		$expiration = time() + ( $remember ? $this->ttl : 2 * DAY_IN_SECONDS );

		// Set the new request with the new key and secret.
		$app_pass_data = WP_Application_Passwords::create_new_application_password(
			$user_id,
			[
				'app_id' => self::APP_ID,
				'name'   => "{$this->app_name} | ID {$this->get_session_token()} | EXP {$expiration}",
			]
		);

		if ( is_wp_error( $app_pass_data ) || empty( $app_pass_data[0] ) || empty( $app_pass_data[1] ) ) {
			return [];
		}

		$this->new_application_password = array_merge( $app_pass_data[1], [ 'unhashed_password' => $app_pass_data[0] ] );
		return $this->new_application_password;
	}

	/**
	 * Format cookie and prepare it to be used in app requests.
	 *
	 * @param string $username User's login.
	 * @param string $password Application password.
	 * @return string
	 */
	protected function get_formatted_token_cookie( string $username, string $password ) : string {
		return base64_encode( "{$username}:{$password}" );
	}

	/**
	 * Set cookies.
	 *
	 * @param string $token_value Value for the token cookie.
	 * @param string $uuid_value  Value for the UUID cookie.
	 * @param bool   $remember    Should this be a session cookie or full TTL.
	 * @return bool
	 */
	protected function set_cookies( string $token_value, string $uuid_value, bool $remember = true ): bool {
		// Front-end cookie is secure when the auth cookie is secure and the site's home URL uses HTTPS.
		$secure_logged_in_cookie = is_ssl() && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		$expiration = $remember ? time() + $this->ttl : 0;

		setcookie(
			self::TOKEN_COOKIE_NAME,
			$token_value,
			$expiration,
			COOKIEPATH,
			$this->cookie_domain,
			$secure_logged_in_cookie,
			false
		);

		setcookie(
			self::UUID_COOKIE_NAME,
			$uuid_value,
			$expiration,
			COOKIEPATH,
			$this->cookie_domain,
			$secure_logged_in_cookie,
			false
		);

		return true;
	}

	/**
	 * Get the current session token.
	 *
	 * @return string
	 */
	protected function get_session_token(): string {
		if ( empty( $this->session_token ) ) {
			$this->session_token = wp_get_session_token();
		}

		return $this->session_token;
	}

	/**
	 * Was the application password generated by Irving?
	 *
	 * @param array $application_password Application Password.
	 * @return bool
	 */
	protected function is_application_password_from_irving( array $application_password ): bool {
		return 0 === strpos( $application_password['name'], $this->app_name );
	}

	/**
	 * Is the given application password expired?
	 *
	 * @param array $application_password Application password.
	 * @return bool
	 */
	protected function is_application_password_expired( array $application_password ): bool {
		// Attempt to extract the expiration from the token name.
		$expiration = (int) substr(
			$application_password['name'],
			strpos( $application_password['name'], '| EXP ' ) + 6
		);

		if ( $expiration ) {
			return $expiration < time();
		} else {
			// If the expiration couldn't be found, leverage the creation date.
			return $application_password['created'] < time() - $this->ttl;
		}
	}
}
