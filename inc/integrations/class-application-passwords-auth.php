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
	 * Application ID for WP Irving. This was randomly generated.
	 */
	const APP_ID = '1481e1b4-8e40-44ed-9b6d-92ba5066ab20';

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
	const UUID_COOKIE_NAME = 'authorizationTokenUUID';

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
	 * Setup the singleton. Validate Application Passswords are availble, and setup hooks.
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

		// Whenever WordPress sets the auth cookie, also set the app password cookies.
		add_action( 'set_auth_cookie', [ $this, 'action_set_auth_cookie' ] );

		// Ensure auth errors fail silently, as to not break the Irving frontend.
		add_filter( 'rest_authentication_errors', [ $this, 'handle_authentication_errors' ], 99 );
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
	 * Set Irving application password cookies when the WP auth cookie is set.
	 */
	public function action_set_auth_cookie() {
		$this->orchestrate_cookies();
	}

	/**
	 * Orchestrate the cookie generation and setting process.
	 *
	 * @return bool
	 */
	public function orchestrate_cookies() {
		$current_password = $this->get_current_session_application_password( get_current_user_id() );

		if ( empty( $current_password ) ) {
			// Get a new application password.
			$current_password = $this->create_application_password();
			$token_value = $this->get_formatted_token_cookie( $current_password['unhashed_password'] );

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
	public function delete_irving_application_passwords( $last_used_before = null ) {
		$user_id       = get_current_user_id();
		$app_passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );

		// Loop through all passwords for this user to find those with a matching name.
		foreach ( $app_passwords as $app_password ) {
			if ( 0 === strpos( $app_password['name'], $this->app_name ) ) {
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
	protected function get_current_session_application_password( $user_id ) {
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
	protected function get_verified_application_password( $uuid, $password, $user_id ) {
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
	protected function get_password_from_formatted_token( $token ) {
		$values = explode( ':', base64_decode( $token ) );
		return ! empty( $values[1] ) ? $values[1] : false;
	}

	/**
	 * Get or create an application password.
	 *
	 * @return array
	 */
	protected function create_application_password() : array {
		$user_id = get_current_user_id();

		// Set the new request with the new key and secret.
		$app_pass_data = WP_Application_Passwords::create_new_application_password(
			$user_id,
			[
				'app_id' => self::APP_ID,
				'name'   => "{$this->app_name} | ID " . wp_get_session_token(),
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
	 * @param string $password Application password.
	 * @return string
	 */
	protected function get_formatted_token_cookie( $password ) : string {
		$user = wp_get_current_user();

		if ( is_wp_error( $user ) ) {
			return '';
		}

		return base64_encode( $user->data->user_login . ':' . $password );
	}

	/**
	 * Set cookies.
	 *
	 * @param string $token_value Value for the token cookie.
	 * @param string $uuid_value  Value for the UUID cookie.
	 */
	protected function set_cookies( $token_value, $uuid_value ): bool {
		// Front-end cookie is secure when the auth cookie is secure and the site's home URL uses HTTPS.
		$secure_logged_in_cookie = is_ssl() && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );

		setcookie(
			self::TOKEN_COOKIE_NAME,
			$token_value,
			$this->ttl,
			COOKIEPATH,
			$this->cookie_domain,
			$secure_logged_in_cookie,
			false
		);

		setcookie(
			self::UUID_COOKIE_NAME,
			$uuid_value,
			$this->ttl,
			COOKIEPATH,
			$this->cookie_domain,
			$secure_logged_in_cookie,
			false
		);

		return true;
	}
}
