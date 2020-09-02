<?php
/**
 * WP Irving integration for Coral.
 *
 * @package WP_Irving;
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;

/**
 * Class to integrate Coral with Irving.
 */
class Coral {
	use Singleton;

	/**
	 * Post type for username records,
	 *
	 * @var string
	 */
	private $post_type = 'wp-irving-coral-user';

	/**
	 * The option key for the integration.
	 *
	 * @var string
	 */
	private $option_key = 'coral';

	/**
	 * Holds the option values to be set.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Setup the singleton. Validate JWT is installed, and setup hooks.
	 */
	public function setup() {
		// Retrieve any existing integrations options.
		$this->options = get_option( 'irving_integrations' );

		// Register settings fields for integrations.
		add_action( 'admin_init', [ $this, 'register_settings_fields' ] );

		// Register a hidden post type for username records.
		add_action( 'init', [ $this, 'register_post_type' ] );

		$sso_secret = $this->options[ $this->option_key ]['sso_secret'] ?? false;

		if ( ! empty( $sso_secret ) ) {
			// Expose data to the endpoint.
			add_filter(
				'wp_irving_data_endpoints',
				function ( $endpoints ) {
					return array_merge( $endpoints, $this->get_endpoint_settings() );
				}
			);
		}
	}

	/**
	 * Register settings fields for display.
	 */
	public function register_settings_fields() {
		// Register new fields for the Coral integration.
		add_settings_field(
			'wp_irving_coral_url',
			esc_html__( 'Coral URL', 'wp-irving' ),
			[ $this, 'render_coral_url_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);

		add_settings_field(
			'wp_irving_coral_sso_secret',
			esc_html__( 'Coral SSO Secret', 'wp-irving' ),
			[ $this, 'render_coral_sso_secret_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);
	}

	/**
	 * Register a post type for internal username record storage.
	 */
	public function register_post_type() {
		register_post_type(
			$this->post_type,
			[
				'public'       => false,
				'show_in_rest' => false,
				'supports'     => [ 'title', 'excerpt' ],
			]
		);
	}

	/**
	 * Render an input for the Coral URL.
	 */
	public function render_coral_url_input() {
		// Check to see if there is an existing SSO secret in the option.
		$coral_url = $this->options[ $this->option_key ]['url'] ?? '';

		?>
			<input type="text" name="irving_integrations[<?php echo esc_attr( 'coral_url' ); ?>]" value="<?php echo esc_attr( $coral_url ); ?>" />
		<?php
	}

	/**
	 * Render an input for the Coral SSO secret.
	 */
	public function render_coral_sso_secret_input() {
		// Check to see if there is an existing SSO secret in the option.
		$sso_secret = $this->options[ $this->option_key ]['sso_secret'] ?? '';

		?>
			<input type="text" name="irving_integrations[<?php echo esc_attr( 'coral_sso_secret' ); ?>]" value="<?php echo esc_attr( $sso_secret ); ?>" />
		<?php
	}

	/**
	 * Get the endpoint settings.
	 *
	 * @return array Endpoint settings.
	 */
	public function get_endpoint_settings(): array {
		return [
			[
				'slug'     => 'validate_sso_user',
				'callback' => [ $this, 'process_validate_endpoint_request' ],
			],
			[
				'slug'     => 'set_sso_username',
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'process_set_username_endpoint_request' ],
			],
			[
				'slug'     => 'check_sso_username_availability',
				'callback' => [ $this, 'process_username_availability_request' ],
			],
		];
	}

	/**
	 * Get the data for the Pico user endpoint verification request.
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public function process_validate_endpoint_request( \WP_REST_Request $request ) {
		// Allow access from the frontend.
		header( 'Access-Control-Allow-Origin: ' . home_url() );

		$user_obj = [
			'id'    => sanitize_text_field( $request->get_param( 'id' ) ),
			'email' => sanitize_text_field( $request->get_param( 'user' ) ),
		];

		// Verify the user's credentials.
		$verified_data = apply_filters( 'wp_irving_verify_coral_user', $user_obj );

		// Bail early if the verified user isn't confirmed by the verification filter.
		if (
			empty( $verified_data ) ||
			empty( $verified_data['id'] ) ||
			empty( $verified_data['email'] )
		) {
			return [ 'status' => 'failed' ];
		}

		// Check for existing username, since the verification call returned 200 OK.
		$username = $this->get_username( $verified_data['id'] );

		if ( '' !== $username ) {
			$credentials = [
				'jti'  => uniqid(),
				'exp'  => time() + ( 90 * DAY_IN_SECONDS ), // JWT will expire in 90 days.
				'iat'  => time(),
				'user' => [
					'id'       => $verified_data['id'],
					'email'    => $verified_data['email'],
					'username' => $username,
				],
			];

			return [
				'status'           => 'success',
				'require_username' => false,
				'jwt'              => $this->build_jwt( $credentials ),
			];
		}

		// If username is not set then return the hash scheme.
		return [
			'status'            => 'success',
			'require_username'  => true,
			'username_set_hash' => $this->create_username_set_hash( $verified_data['id'], $verified_data['email'] ),
		];
	}

	/**
	 * Accept requests to set a username for a given email address.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return array|\WP_REST_Response
	 */
	public function process_set_username_endpoint_request( \WP_REST_Request $request ) {
		$params   = json_decode( $request->get_body() );
		$id       = sanitize_text_field( $params->id );
		$username = sanitize_text_field( $params->username );
		$hash     = sanitize_text_field( $params->hash );

		// The security check is performed in this function.
		$status = $this->set_username( $id, $username, $hash );

		if ( $status ) {
			return [
				'status'   => 'success',
				'id'       => $id,
				'username' => $username,
			];
		}

		return new \WP_REST_Response(
			[
				'status' => 'unauthorized',
			],
			403
		);
	}

	/**
	 * Handle username availability checks.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	public function process_username_availability_request( \WP_REST_Request $request ) {
		$username = sanitize_text_field( $request->get_param( 'username' ) );

		if ( ! $username || '' === $username ) {
			return [
				'username'  => '',
				'available' => false,
			];
		}

		return [
			'username'  => $username,
			'available' => ! $this->username_exists( $username ),
		];
	}

	/**
	 * Construct a HS256-encrypted JWT for SSO authentication.
	 *
	 * @param array $credentials The user to be authenticated.
	 * @return string The constructed JWT.
	 */
	public function build_jwt( array $credentials ): string {
		// Define the JWT header and payload.
		$header  = wp_json_encode(
			[
				'typ' => 'JWT',
				'alg' => 'HS256',
			]
		);
		$payload = wp_json_encode( $credentials );
		$secret  = $this->options[ $this->option_key ]['sso_secret'];

		// Base64 URL encode the header and payload.
		$base64_header  = $this->base64url_encode( $header );
		$base64_payload = $this->base64url_encode( $payload );

		// Generate the JWT signature.
		$signature = hash_hmac( 'sha256', $base64_header . '.' . $base64_payload, $secret, true );
		// Base64 URL encode the signature.
		$base64_signature = $this->base64url_encode( $signature );

		// Return the built JWT.
		return $base64_header . '.' . $base64_payload . '.' . $base64_signature;
	}

	/**
	 * Base64 URL encode a target data string.
	 *
	 * @param string $data The data to be encoded.
	 * @return string The encoded data.
	 */
	public function base64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Retrieve a username record. These functions use the post_title column
	 * for the user's SSO provider ID, and the post_excerpt column for the username.
	 *
	 * @param string $id The SSO ID of the user.
	 * @return string The username, or a blank string if none is set.
	 */
	private function get_username( $id ) : string {
		global $wpdb;

		$username = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( 
				"SELECT post_excerpt 
					FROM {$wpdb->posts} 
				WHERE 
					post_title=%s AND
					post_type=%s AND
					post_status='publish'
				LIMIT 1 ",
				[
					$id,
					$this->post_type,
				]
			)
		);

		return $username ?? '';
	}

	/**
	 * Retrieve a username record's post ID. These functions use the post_title column
	 * for the user's SSO provider ID, and the post_excerpt column for the username.
	 *
	 * @param string $id The SSO ID of the user.
	 * @return int The post ID, or 0 if none is found.
	 */
	private function get_username_post_id( $id ) : int {
		global $wpdb;

		$post_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( 
				"SELECT ID 
					FROM {$wpdb->posts} 
				WHERE 
					post_title=%s AND
					post_type=%s AND
					post_status='publish'
				LIMIT 1 ",
				[
					$id,
					$this->post_type,
				]
			)
		);

		return $post_id ?? 0;
	}

	/**
	 * Check if a username is available. These functions use the post_title column
	 * for the user's SSO provider ID, and the post_excerpt column for the username.
	 *
	 * @param string $username The username to check.
	 * @return bool Whether the name is already in use (true) or not (false).
	 */
	private function username_exists( $username ) : int {
		global $wpdb;

		if ( ! $username || '' === $username ) {
			return false;
		}

		$post_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( 
				"SELECT ID 
					FROM {$wpdb->posts} 
				WHERE 
					post_excerpt=%s AND
					post_type=%s AND
					post_status='publish'
				LIMIT 1 ",
				[
					$username,
					$this->post_type,
				]
			)
		) ?? 0;

		return ( 0 < $post_id );
	}

	/**
	 * Create or update a username record. These functions use the post_title column
	 * for the user's SSO provider ID, and the post_excerpt column for the username.
	 *
	 * @param string $id       The SSO ID of the user.
	 * @param string $username The associated username.
	 * @param string $hash     The security hash verifying the request.
	 * @return bool Whether the create/update operation succeeded.
	 */
	private function set_username( $id, $username, $hash ) : bool {
		$key         = 'username_set_hash_' . md5( $id );
		$stored_hash = get_transient( $key );

		// Stop if the hash doesn't exist, wasn't passed, or doesn't match the one on file. 
		if ( ! $stored_hash || ! $hash || $hash !== $stored_hash ) {
			return false;
		}

		// Stop if the username is already taken by someone else.
		if ( $this->username_exists( $username ) ) {
			return false;
		}

		$args = [
			'post_title'   => $id,
			'post_excerpt' => $username,
			'post_body'    => '',
			'post_type'    => $this->post_type,
			'post_status'  => 'publish',
		];

		$post_id = $this->get_username_post_id( $id );
		
		if ( 0 < $post_id ) {
			$args['ID'] = $post_id;
		}

		$post = wp_insert_post( $args );

		return ( ! is_wp_error( $post ) );
	}

	/**
	 * Create a one-time use hash key for username setting.
	 *
	 * @param string $id    The SSO ID for this user.
	 * @param string $email The email for which the hash is valid.
	 * @return string The hash.
	 */
	private function create_username_set_hash( $id, $email ) : string {
		$message = implode(
			':',
			[
				$id,
				$email,
				strval( microtime() ),
				strval( random_int( 0, 32768 ) ),
			]
		);
		$hash    = hash( 'sha256', $message );
		$key     = 'username_set_hash_' . md5( $id );

		set_transient( $key, $hash, 3600 );

		return $hash;
	}
}
