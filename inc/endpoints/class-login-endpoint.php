<?php
/**
 * Class file for login endpoints.
 *
 * @package WP_Irving
 */

namespace WP_Irving\REST_API;

/**
 * Login Endpoint.
 */
class Login_Endpoint extends Endpoint {

	/**
	 * Attach to required hooks for login endpoint
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register the REST API routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			self::get_namespace(),
			'/login/',
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'get_route_response' ],
				'args'     => array(
					'username'    => array(
						'description'       => esc_html__( 'The user name or email address.', 'wp-irving' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'password' => array(
						'description'       => esc_html__( 'The user password.', 'wp-irving' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
			]
		);
	}

	/**
	 * Callback for the route.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_Error|WP_REST_Response
	 */
	public function get_route_response( \WP_REST_Request $request ) {

		// JWT Auth plugin needs to be installed and active.
		if ( ! class_exists( '\WP_REST_Key_Pair' ) ) {
			return new \WP_Error(
				'rest_authentication_jwt_auth_plugin_required',
				__( 'The JWT Auth needs to be installed and active for this endpoint to work correctly.', 'wp-irving' ),
				[
					'status' => 500,
				]
			);
		}

		// Authenticate user directly.
		$user = wp_authenticate( $request['username'], $request['password'] );
		if ( ! $user instanceof \WP_User ) {
			return new \WP_Error(
				'rest_authentication_invalid_user',
				__( 'There was a problem authenticating the user.', 'wp-irving' ),
				[
					'status' => 403,
				]
			);
		}

		// Settings variables.
		$user_id          = $user->ID;
		$api_key          = $user_id . wp_generate_password( 24, false );
		$api_secret       = wp_generate_password( 32 );

		/**
		 * Here are saving the new keypairs. This information is important
		 * in case the user wishes to remove the token validation via their user profile.
		 */
		$wp_rest_keypair = new \WP_REST_Key_Pair();
		$keypairs        = $wp_rest_keypair->get_user_key_pairs( $user_id );
		$keypairs[]      = [
			'name'       => 'wp-irving-' . $api_key,
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
		return rest_do_request( $token_request );
	}
}

new Login_Endpoint();
