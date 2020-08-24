<?php
/**
 * WP Irving integration for Google Analytics.
 *
 * @package WP_Irving;
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;

/**
 * Class to integrate Google Analytics with Irving.
 */
class Google_Analytics {
	use Singleton;

	/**
	 * The option key for the integration.
	 *
	 * @var string
	 */
	private $option_key = 'google_analytics';

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
		// Register a new field for the Google Analytics integration.
		add_settings_field(
			'wp_irving_ga_tracking_id',
			esc_html__( 'Google Analytics Tracking ID', 'wp-irving' ),
			[ $this, 'render_tracking_id_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);
	}

	/**
	 * Render an input for the GA Tracking ID.
	 */
	public function render_tracking_id_input() {
		// Check to see if there is an existing GA configuration in the option.
		$ga_key = $this->options[ $this->option_key ]['tracking_id'] ?? '';

		?>
			<input type="text" name="irving_integrations[<?php echo esc_attr( 'ga_tracking_id' ); ?>]" value="<?php echo esc_attr( $ga_key ); ?>" />
		<?php
	}
}
