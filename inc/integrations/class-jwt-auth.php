<?php
/**
 * JWT Authentication Integration.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Singleton class for creating a cross-domain cookie with a JSON Web Token
 * that Irving core can read and use for Component API authentication.
 */
class JWT_Auth {

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
	 * Class instance.
	 *
	 * @var null|self
	 */
	protected static $instance;

	/**
	 * Get class instance
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
			static::$instance->setup();
		}
		return static::$instance;
	}

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

		// Don't require the token to be valid. This ensures that even if the
		// value of the cookie is an invalid token, the unauthenticated
		// endpoint is returned rather than an error message.
		add_filter( 'rest_authentication_require_token', '__return_false' );
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
		$token_response = $this->get_or_create_token();

		// Invalid response. This needs better error handing.
		if ( empty( $token_response ) ) {
			return false;
		}

		// Set a cross domain cookie using the JWT.
		// phpcs:ignore
		setcookie(
			self::COOKIE_NAME,
			$token_response['access_token'] ?? '',
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

		if (
			! isset( $_COOKIE[ self::COOKIE_NAME ] ) // phpcs:ignore
			|| is_user_logged_in()
		) {
			 return false;
		}

		// phpcs:ignore
		setcookie(
			self::COOKIE_NAME,
			null,
			-1,
			'/',
			$this->cookie_domain
		);

		return true;
	}

	/**
	 * Get or create a JSON Web Token.
	 *
	 * @return array
	 */
	public function get_or_create_token() : array {

		$user_id    = get_current_user_id();
		$api_key    = $user_id . wp_generate_password( 24, false );
		$api_secret = wp_generate_password( 32 );

		/**
		 * Here are saving the new keypairs. This information is important
		 * in case the user wishes to remove the token validation via their user profile.
		 */
		$wp_rest_keypair = new \WP_REST_Key_Pair();
		$keypairs        = $wp_rest_keypair->get_user_key_pairs( $user_id );

		// Delete any existing keypairs.
		foreach ( $keypairs as $index => $keypair ) {
			if ( self::KEYPAIR_NAME === $keypair['name'] ) {
				unset( $keypairs[ $index ] );
			}
		}

		$keypairs[]      = [
			'name'       => self::KEYPAIR_NAME,
			'api_key'    => $api_key,
			'api_secret' => wp_hash( $api_secret ),
			'created'    => time(),
			'last_used'  => null,
			'last_ip'    => null,
		];

		// Saving the new key.
		$wp_rest_keypair->set_user_key_pairs( $user_id, $keypairs );

		// Set the new request with the new key and secret.
		$token_request = new \WP_REST_Request( \WP_REST_Server::CREATABLE, '/wp/v2/token' );
		$token_request->set_query_params(
			[
				'api_key'    => $api_key,
				'api_secret' => $api_secret,
			]
		);

		// Let's get the token.
		$token_request = rest_do_request( $token_request );

		if ( 200 === $token_request->status ?? 0 ) {
			return $token_request->data;
		}

		return [];
	}
}

( new \WP_Irving\JWT_Auth() )->instance();
