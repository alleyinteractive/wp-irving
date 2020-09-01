<?php
/**
 * WP Irving integration for Pico.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;
use Pico_Menu;
use Pico_Setup;
use Pico_Widget;

/**
 * Class to integrate Pico with Irving.
 */
class Pico {
	use Singleton;

	/**
	 * Request path to verify users with Pico.
	 *
	 * @var string
	 */
	private $verify_user_path = 'users/verify';

	/**
	 * The option key for the integration.
	 *
	 * @var string
	 */
	private $option_key = 'pico';

	/**
	 * Holds the option values to be set.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Setup the integration.
	 */
	public function setup() {
		// Ensure Pico exists and is enabled.
		if ( ! defined( 'PICO_VERSION' ) ) {
			return;
		}

		// Retrieve any existing integrations options.
		$this->options = get_option( 'irving_integrations' );

		// Register settings fields for integrations.
		add_action( 'admin_init', [ $this, 'register_settings_fields' ] );

		if ( ! is_admin() ) {
			// Filter the integrations manager to include our Pico props.
			add_filter( 'wp_irving_integrations_option', [ $this, 'inject_pico' ] );

			// Wrap content with `<div id="pico"></div>`.
			add_filter( 'the_content', [ 'Pico_Widget', 'filter_content' ] );
		}

		add_filter( 'wp_irving_verify_coral_user', [ $this, 'verify_pico_user_for_sso' ] );
	}

	/**
	 * Register settings fields for display.
	 */
	public function register_settings_fields() {
		// Register new fields for the Coral integration.
		add_settings_field(
			'wp_irving_pico_auth_key',
			esc_html__( 'Pico API Basic Auth Key', 'wp-irving' ),
			[ $this, 'render_pico_auth_key_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);
	}

	/**
	 * Render an input for the Pico Basic Auth Key.
	 */
	public function render_pico_auth_key_input() {
		// Check to see if there is an existing SSO secret in the option.
		$pico_auth_key = $this->options[ $this->option_key ]['auth_key'] ?? '';

		?>
			<input type="text" name="irving_integrations[<?php echo esc_attr( 'pico_auth_key' ); ?>]" value="<?php echo esc_attr( $pico_auth_key ); ?>" />
		<?php
	}

	/**
	 * Inject Pico props into the integrations manager option.
	 *
	 * @param array $options Integrations option array.
	 * @return array Updated options.
	 */
	public function inject_pico( array $options ): array {
		// Get and validate the publisher id.
		$publisher_id = Pico_Setup::get_publisher_id();
		if ( empty( $publisher_id ) ) {
			return $options;
		}

		$options['pico'] = [
			'publisher_id' => Pico_Setup::get_publisher_id(),
			'page_info'    => Pico_Widget::get_current_view_info(),
		];

		// Taxonomies always need to be an object.
		$options['pico']['page_info']['taxonomies'] = (object) ( $options['pico']['page_info']['taxonomies'] ?? [] );

		return $options;
	}

	/**
	 * Validate a Pico user's credentials and return the required credentials
	 * to build a JWT.
	 *
	 * @param array $user The initial user object.
	 * @return array|bool Updated user object or false if verification fails.
	 */
	public function verify_pico_user_for_sso( array $user ) {
		// TODO: Dispatch a verification request to the Pico API. If the user
		// is verified, return the constructed user with an ID, email, and
		// username.
		// If the user isn't verified, return false, which will cause a failure
		// response to be returned on the front-end and the appropriate behavior
		// will be triggered.
		$payload = [
			'id'    => $user['id'],
			'email' => $user['email'],
		];

		$credentials = [
			'http_username' => Pico_Setup::get_publisher_id(),
			'http_password' => $this->options[ $this->option_key ]['auth_key'] ?? '',
		];

		$response = $this->verification_request( $this->verify_user_path, $payload, $credentials );

		if ( 200 !== $response['status_code'] ) {
			return false;
		}

		return $response;
	}

	/**
	 * Do an HTTP basic auth query to Pico's verification API. We can't reuse the method in
	 * the Pico plugin because it doesn't support Basic Auth.
	 *
	 * @param string $path        The request path to query (e.g. '/users/verify').
	 * @param array  $payload     The payload for the request.
	 * @param array  $credentials Basic auth credentials (keys 'http_username', 'http_password').
	 * @return array The response.
	 */
	private function verification_request( $path, $payload, $credentials ) : array {
		$http_args = [
			'body'    => json_encode( $payload ),
			'headers' => [
				'Content-Type'  => 'application/json; charset=' . get_option( 'blog_charset' ),
				'Accept'        => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $credentials['http_username'] . ':' . $credentials['http_password'] ),
			],
		];

		$request_url = Pico_Setup::get_api_endpoint() . "/" . $path;
		$response    = wp_remote_post( $request_url, $http_args );
		$raw_body    = wp_remote_retrieve_body( $response );

		if ( ! $raw_body ) {
			return [ 'status_code' => 400 ];
		}

		$body = json_decode( $raw_body, true );

		if ( empty( $body['status_code'] ) ) {
			$body['status_code'] = wp_remote_retrieve_response_code( $response );
		}

		return $body;
	}
}
