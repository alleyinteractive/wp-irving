<?php
/**
 * WP Irving integration for Pico.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;
use WP_Irving\Integrations;
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

			// Possibly wrap content with `<div id="pico"></div>`.
			add_filter( 'the_content', __CLASS__ . '::filter_content' );
		}

		add_filter( 'wp_irving_verify_coral_user', [ $this, 'verify_pico_user_for_sso' ] );

		// Use staging API urls.
		if ( get_option( 'irving_integrations' )['pico']['use_staging'] ?? false ) {
			if ( ! defined( 'PP_API_ENDPOINT' ) ) {
				define( 'PP_API_ENDPOINT', 'https://api.staging.pico.tools' ); // phpcs:ignore
			}

			if ( ! defined( 'PP_WIDGET_ENDPOINT' ) ) {
				define( 'PP_WIDGET_ENDPOINT', 'https://gadget.staging.pico.tools' ); // phpcs:ignore
			}
		}
	}

	/**
	 * Possibly filter the content for the Pico paywall.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function filter_content( string $content ): string {
		/**
		 * Post types which enable the paywall.
		 *
		 * @param array $paywall_post_types Post types.
		 */
		$paywall_post_types = apply_filters( 'wp_irving_pico_paywall_post_types', [ 'post' ] );

		if ( ! in_array( get_post_type(), $paywall_post_types, true ) ) {
			return $content;
		}

		// Wrap content with `<div id="pico"></div>`.
		return '<div id="pico">' . $content . '</div>';
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

		// Register new fields for the Coral integration.
		add_settings_field(
			'wp_irving_pico_tiers',
			esc_html__( 'Allowed Pico/Coral SSO Tiers', 'wp-irving' ),
			[ $this, 'render_pico_tiers_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);

		// Register new fields for the Coral integration.
		add_settings_field(
			'wp_irving_pico_use_staging_urls',
			esc_html__( 'Use Pico\'s staging API?', 'wp-irving' ),
			[ $this, 'render_pico_use_staging_input' ],
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
	 * Render a textarea for the Pico allow listed SSO Tiers.
	 */
	public function render_pico_tiers_input() {
		// Check to see if there are existing tiers in the option.
		$tiers = $this->options[ $this->option_key ]['tiers'] ?? '';

		?>
			<textarea id="pico_tiers" rows="5" cols="50" type="text" name="irving_integrations[<?php echo esc_attr( 'pico_tiers' ); ?>]"><?php echo esc_textarea( $tiers ); ?></textarea>
			<label for="pico_tiers">
				<p>
					<em><?php echo esc_html__( 'Tiers should be input as comma-separated values (e.g. Reader,Subscriber)', 'wp-irving' ); ?></em>
				</p>
			</label>
		<?php
	}

	/**
	 * Render a checkbox which enables the staging API urls.
	 */
	public function render_pico_use_staging_input() {
		$use_staging = wp_validate_boolean( $this->options[ $this->option_key ]['use_staging'] ?? false );
		$is_checked  = $use_staging ? 'checked' : '';

		?>
			<input type="checkbox" name="irving_integrations[<?php echo esc_attr( 'pico_use_staging' ); ?>]" value="true" <?php echo esc_attr( $is_checked ); ?> />
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
			'widget_url'   => Pico_Setup::get_widget_endpoint(),
		];

		$tiers = Integrations\get_option_value( 'pico', 'tiers' );

		if ( ! empty( $tiers ) ) {
			$options['pico']['tiers'] = array_filter( array_map( 'trim', explode( ',', $tiers ) ) );
		}

		/**
		 * Filter Pico options for the integrations manager.
		 *
		 * @param array Pico options.
		 */
		$options['pico'] = apply_filters( 'wp_irving_pico_options', $options['pico'] );

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
			// Provide a hook for logging errors.
			do_action( 'wp_irving_verify_pico_user_api_error', $response );
			return false;
		}

		return [
			'id'    => $response['id'],
			'email' => $response['user']['email'],
		];
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
			'body'    => wp_json_encode( $payload ),
			'headers' => [
				'Content-Type'  => 'application/json; charset=' . get_option( 'blog_charset' ),
				'Accept'        => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $credentials['http_username'] . ':' . $credentials['http_password'] ),
			],
		];

		$request_url = Pico_Setup::get_api_endpoint() . '/' . $path;
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
