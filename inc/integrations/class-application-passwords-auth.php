<?php
/**
 * WP Core Application Passwords Authentication Integration.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

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
		add_action( 'set_auth_cookie', [ $this, 'action_set_auth_cookie' ], 10, 6 );

		// When a user logs in, set the app password cookies.
		add_action( 'wp_login', [ $this, 'action_wp_login' ], 10, 2 );

		// When WP successfully auths an app password, maybe update cookies.
		add_action( 'application_password_did_authenticate', [ $this, 'action_application_password_did_authenticate' ], 10, 2 );

		// When WP fails authentication an app password, maybe remove cookies.
		add_action( 'application_password_failed_authentication', [ $this, 'action_application_password_failed_authentication' ] );

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
	 * @todo If "remember" is not checked, should this change the cookie ttl? If
	 *       `$expire === 0`, that means "remember" was not checked.
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
	public function action_set_auth_cookie( $auth_cookie, $expire, $expiration, $user_id, $scheme, $token ) {
		$user = wp_get_current_user();
		if ( ! empty( $user->ID ) ) {
			$this->session_token = $token;
			$this->orchestrate_cookies( $user->ID, $user->user_login );
		}
	}

	/**
	 * Set or update token cookies on successful login.
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user       WP_User object of the logged-in user.
	 */
	public function action_wp_login( $user_login, $user ) {
		$this->orchestrate_cookies( $user->ID, $user_login );
	}

	/**
	 * If the current session's application password was successfully
	 * authenticated, update the cookies.
	 *
	 * @param WP_User $user                 Authenticated user. Unused.
	 * @param array   $application_password Application password item.
	 */
	public function action_application_password_did_authenticate( WP_User $user, array $application_password ) {
		if (
			isset( $_COOKIE[ self::UUID_COOKIE_NAME ], $_COOKIE[ self::TOKEN_COOKIE_NAME ] )
			&& $_COOKIE[ self::UUID_COOKIE_NAME ] === $application_password['uuid']
		) {
			$this->set_cookies( $_COOKIE[ self::TOKEN_COOKIE_NAME ], $application_password['uuid'] );
		}
	}

	/**
	 * If the application password in the cookie was used and authentication
	 * failed, remove the cookies.
	 */
	public function action_application_password_failed_authentication() {
		if (
			isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_COOKIE[ self::TOKEN_COOKIE_NAME ] )
			&& $_COOKIE[ self::TOKEN_COOKIE_NAME ] === $this->get_formatted_token_cookie( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] )
		) {
			$this->remove_cookies();
		}
	}

	/**
	 * Orchestrate the cookie generation and setting process.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $user_login User login.
	 * @return bool
	 */
	public function orchestrate_cookies( int $user_id, string $user_login ): bool {
		$current_password = $this->get_current_session_application_password( $user_id );

		if ( empty( $current_password ) ) {
			// Get a new application password.
			$current_password = $this->create_application_password( $user_id );
			if ( empty( $current_password['unhashed_password'] ) ) {
				return false;
			}

			$token_value = $this->get_formatted_token_cookie(
				$user_login,
				$current_password['unhashed_password']
			);

			// Prune the old application passwords.
			$this->prune_old_application_passwords();
		} else {
			$token_value = $_COOKIE[ self::TOKEN_COOKIE_NAME ];
		}

		// Invalid response. This needs better error handing.
		if ( empty( $current_password ) ) {
			return false;
		}

		return $this->set_cookies( $token_value, $current_password['uuid'] );
	}

	/**
	 * Remove old application passwords that haven't been used since the TTL
	 * expiration.
	 */
	public function prune_old_application_passwords() {
		$this->delete_irving_application_passwords( time() - $this->ttl );
	}

	/**
	 * Delete all old Irving application passwords for the current user.
	 *
	 * @param null|int $last_used_before Optional. Timestamp. If present, only
	 *                                   application passwords last used before
	 *                                   the given timestamp will be removed,
	 *                                   adding in a day of buffer (since the
	 *                                   last_used timestamp is only updated
	 *                                   once per day).
	 */
	public function delete_irving_application_passwords( ?int $last_used_before = null ) {
		$user_id       = get_current_user_id();
		$app_passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );

		// Loop through all passwords for this user to find those with a matching name.
		foreach ( $app_passwords as $app_password ) {
			if ( ! empty( $app_password['last_used'] ) && 0 === strpos( $app_password['name'], $this->app_name ) ) {
				// Optionally check the last_used date.
				if ( $last_used_before && $app_password['last_used'] + DAY_IN_SECONDS > $last_used_before ) {
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
	 * @param int $user_id User ID.
	 * @return array
	 */
	protected function create_application_password( int $user_id ) : array {
		// Set the new request with the new key and secret.
		$app_pass_data = WP_Application_Passwords::create_new_application_password(
			$user_id,
			[
				'app_id' => self::APP_ID,
				'name'   => "{$this->app_name} | ID " . $this->get_session_token(),
			]
		);

		if ( is_wp_error( $app_pass_data ) || empty( $app_pass_data[0] ) || empty( $app_pass_data[1] ) ) {
			return [];
		}

		return array_merge( $app_pass_data[1], [ 'unhashed_password' => $app_pass_data[0] ] );
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
	 */
	protected function set_cookies( string $token_value, string $uuid_value ): bool {
		// Front-end cookie is secure when the auth cookie is secure and the site's home URL uses HTTPS.
		$secure_logged_in_cookie = is_ssl() && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );

		setcookie(
			self::TOKEN_COOKIE_NAME,
			$token_value,
			time() + $this->ttl,
			COOKIEPATH,
			$this->cookie_domain,
			$secure_logged_in_cookie,
			false
		);

		setcookie(
			self::UUID_COOKIE_NAME,
			$uuid_value,
			time() + $this->ttl,
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
}
