<?php
/**
 * Previews.
 *
 * This functionality ensures that authenticated previews still work in Irving.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

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
	 * Cookie domain. Used to enable cross-domain cookie auth.
	 *
	 * @var string
	 */
	public $cookie_domain = '';

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
	 * Setup the singleton.
	 *
	 * @todo Determine if we need to validate the token. This may not be needed
	 * because the cookie is set to expire right before the auth token does.
	 * Maybe we should hook into if/when the token is revoked so we can kill
	 * the cookie too?
	 */
	public function setup() {

		$this->cookie_domain = defined( 'IRVING_TLD' ) ? IRVING_TLD : wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			add_action( 'admin_init', [ $this, 'set_cookie' ] );
		} else {
			add_action( 'init', [ $this, 'remove_cookie' ] );
		}
	}

	/**
	 * Get a clean token and set the cookie.
	 */
	public function set_cookie() {
		$token_response = $this->get_or_create_token();

		// Set a cross domain cookie using the JWT.
		setcookie(
			self::COOKIE_NAME,
			$token_response['access_token'],
			time() + ( DAY_IN_SECONDS * 7 ) - MINUTE_IN_SECONDS,
			'/',
			$this->cookie_domain,
			TRUE,
			FALSE
		);
	}

	/**
	 * If the user isn't logged in, ensure the auth cookie is deleted.
	 */
	public function remove_cookie() {
		if ( ! is_user_logged_in() ) {
			setcookie( self::COOKIE_NAME, null, -1, '/', $this->cookie_domain );
		}
	}

	/**
	 * [get_or_create_wp_irving_preview_token description]
	 * @return [type] [description]
	 */
	public function get_or_create_token() {

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
			if ( $keypair['name'] === self::KEYPAIR_NAME ) {
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
		$keys = (array) $wp_rest_keypair->set_user_key_pairs( $user_id, $keypairs );

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

		return false;
	}
}

( new \WP_Irving\JWT_Auth() )->instance();
