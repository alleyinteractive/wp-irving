<?php
/**
 * WP Irving integration for Pico.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;
use WP_Irving\Integrations;
use Pico_Setup;
use Pico_Widget;

/**
 * Class to integrate Pico with Irving.
 */
class Pico {
	use Singleton;

	/**
	 * Setup the integration.
	 */
	public function setup() {

		// Ensure Pico exists and is enabled.
		if ( ! defined( 'PICO_VERSION' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			// Filter the integrations manager to include our Pico props.
			add_filter( 'wp_irving_integrations_option', [ $this, 'inject_pico' ] );

			// Wrap content with `<div id="pico"></div>`.
			add_filter( 'the_content', [ 'Pico_Widget', 'filter_content' ] );
		}

		// Register settings fields for integrations.
		add_action( 'admin_init', [ $this, 'register_settings_fields' ] );

		add_filter( 'wp_irving_verify_coral_user', [ $this, 'verify_pico_user_for_sso' ] );
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
	 * Register settings fields for display.
	 */
	public function register_settings_fields() {
		// Register new fields for the Coral integration.
		add_settings_field(
			'wp_irving_pico_sso_id',
			esc_html__( 'Pico SSO ID', 'wp-irving' ),
			[ $this, 'render_pico_sso_id_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);

		add_settings_field(
			'wp_irving_pico_sso_key',
			esc_html__( 'Pico SSO Key', 'wp-irving' ),
			[ $this, 'render_pico_sso_key_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);
	}

	/**
	 * Render an input for the Pico SSO ID.
	 */
	public function render_pico_sso_id_input() {
		// Check to see if there is an existing SSO secret in the option.
		$sso_id = Integrations\get_option_value( 'pico', 'sso_id' ) ?? '';

		?>
			<input type="text" name="irving_integrations[<?php echo esc_attr( 'pico_sso_id' ); ?>]" value="<?php echo esc_attr( $sso_id ); ?>" />
		<?php
	}

	/**
	 * Render an input for the Pico SSO Key.
	 */
	public function render_pico_sso_key_input() {
		// Check to see if there is an existing SSO secret in the option.
		$sso_key = Integrations\get_option_value( 'pico', 'sso_key' ) ?? '';

		?>
			<input type="text" name="irving_integrations[<?php echo esc_attr( 'pico_sso_key' ); ?>]" value="<?php echo esc_attr( $sso_key ); ?>" />
		<?php
	}

	/**
	 * Validate a Pico user's credentials and return the required credentials
	 * to build a JWT.
	 *
	 * @param array $user The initial user object.
	 * @return array Updated user object.
	 */
	public function verify_pico_user_for_sso( array $user ) {
		$keys              = Pico_Setup::get_publisher_id(true);
		$pico_publisher_id = Integrations\get_option_value( 'pico', 'sso_id' ) ?? '';
		$pico_api_key      = Integrations\get_option_value( 'pico', 'sso_key' ) ?? '';

		// Bail early if either of the option values are not set.
		if ( empty( $pico_api_key ) || empty( $pico_publisher_id ) ) {
			return false;
		}

		// Dispatch a verification request to the Pico API. If the user
		// is verified, return the constructed user with an ID, email, and
		// username.
		$response = wp_remote_post(
			'https://api.pico.tools/users/verify',
			[
				'method'  => 'POST',
				'body'    => wp_json_encode(
					[
						'email' => $user['email'],
						'id'    => $user['id'],
					]
				),
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . base64_encode( $pico_publisher_id . ':' . $pico_api_key )
				]
			]
		);

		$response_code = $response['response']['code'];

		if ( ! empty( $response_code ) && $response_code === 200 ) {
			$response_body = json_decode( $response['body'] );

			// Update the user's ID value.
			if ( empty( $user['id'] ) ) {
				$user['id'] = $response_body->user->id;
			}

			return $user;
		}

		// If the user isn't verified, return false, which will cause a failure
		// response to be returned on the front-end and the appropriate behavior
		// will be triggered.
		return false;
	}
}
