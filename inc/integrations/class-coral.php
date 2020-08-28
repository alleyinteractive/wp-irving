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

		$sso_secret = $this->options[ $this->option_key ]['sso_secret'] ?? false;

		if ( ! empty( $sso_secret ) ) {
			// Expose data to the endpoint.
			add_filter(
				'wp_irving_data_endpoints',
				function ( $endpoints ) {
					$endpoints[] = $this->get_endpoint_settings();

					return $endpoints;
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
			'slug'     => 'validate_sso_user',
			'callback' => [ $this, 'process_endpoint_request' ],
		];
	}

	/**
	 * Get the data for the Pico user endpoint verification request.
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public function process_endpoint_request( \WP_REST_Request $request ) {
		// Allow access from the frontend.
		header( 'Access-Control-Allow-Origin: ' . home_url() );

		$user = sanitize_text_field( $request->get_param( 'user' ) );

		// Build a base user object for verifiection.
		$user_obj = [
			'id'       => 'asdf',
			'email'    => $user,
			'username' => '',
		];

		// Verify the user's credentials.
		$verified_user = apply_filters( 'wp_irving_verify_coral_user', $user_obj );

		// Bail early if the verified user doesn't exist.
		if ( empty( $verified_user ) ) {
			return [ 'status' => 'failed' ];
		}

		// If the user has been verified to contain a unique ID but does not yet have a
		// username, check the database to see if the user exists. If so, continue, if not
		// return a response that will dispatch an action summoning UI that requires the
		// user to enter a username.
		if ( !empty( $verified_user['id'] ) && empty( $verified_user['username'] ) ) {
			// check to see if the user exists.
			$username = '';

			// Bail early if no user exists;
			if ( empty ( $username ) ) {
				return [
					'status'           => 'success',
					'require_username' => true,
				];
			}
		}

		$credentials = [
			'jti'  => uniqid(),
			'exp'  => time() + ( 90 * DAY_IN_SECONDS ), // JWT will expire in 90 days.
			'iat'  => time(),
			'user' => [
				'id'       => $verified_user['id'],
				'email'    => $verified_user['email'],
				'username' => $verified_user['username'],
			],
		];

		// return [
		// 	'status' => 'success',
		// 	'jwt'    => $this->build_jwt( $credentials ),
		// ];
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
}
