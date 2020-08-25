<?php
/**
 * WP Irving integration for Google Tag Manager.
 *
 * @package WP_Irving;
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;

/**
 * Class to integrate Google Tag Manager with Irving.
 */
class Google_Tag_Manager {
	use Singleton;

	/**
	 * The option key for the integration.
	 *
	 * @var string
	 */
	private $option_key = 'google_tag_manager';

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
	}

	/**
	 * Register settings fields for display.
	 */
	public function register_settings_fields() {
		// Register a new field for the Google Tag Manager integration.
		add_settings_field(
			'wp_irving_gtm_container_id',
			esc_html__( 'Google Tag Manager Container ID', 'wp-irving' ),
			[ $this, 'render_container_id_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);
	}

	/**
	 * Render an input for the GTM Container ID.
	 */
	public function render_container_id_input() {
		// Check to see if there is an existing GTM configuration in the option.
		$gtm_key = $this->options[ $this->option_key ]['container_id'] ?? '';

		?>
			<input type="text" name="irving_integrations[<?php echo esc_attr( 'gtm_container_id' ); ?>]" value="<?php echo esc_attr( $gtm_key ); ?>" />
		<?php
	}
}