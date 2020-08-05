<?php
/**
 * WP Irving integration for Google Analytics.
 *
 * @package WP_Irving;
 */

namespace WP_Irving;

/**
 * Class to integrate Google Analytics with Irving.
 */
class Google_Analytics {

	/**
	 * Holds the option values to be set.
	 */
	private $options;

	/**
	 * Constructor for class.
	 */
	public function __construct() {
		// Retrieve any existing integrations options.
		$this->options = get_option( 'irving_integrations' );

		// Register settings fields for integrations.
		add_action( 'admin_init', [ $this, 'register_settings_fields' ], 10, 2 );
		// Filter the updated option values prior to submission.
		add_filter( 'pre_update_option_irving_integrations', [ $this, 'format_option_for_update' ], 10, 2);
	}

	/**
	 * Register settings fields for display.
	 */
	public function register_settings_fields() {
		// Register a new field for the Google Analytics integration.
		add_settings_field(
			'ga_tracking_id',
			__( 'Google Analytics Tracking ID', 'wp-irving' ),
			[ $this, 'render_tracking_id_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings',
			[
				'id' => 'tracking_id',
			]
		);
	}

	/**
	 * Render an input for the GA Tracking ID.
	 */
	public function render_tracking_id_input( $args ) {
		// Check to see if there is an existing GA configuration in the option.
		$option = isset( $this->options['google_analytics'] ) ? $this->options['google_analytics'][ $args[ 'id' ] ] : '';
		$ga_key = ! empty( $option ) ? $option : '';

		?>
			<input type="text" name="irving_integrations[<?php echo esc_attr( 'ga_' . $args[ 'id' ] ); ?>]" value="<?php echo esc_attr( $ga_key ); ?>" />
		<?php
	}

	/**
	 * Group the updated options based on the integration prefix.
	 *
	 * @param array  $options The updated options.
	 * @return array $arr The formatted options.
	 */
	public function format_option_for_update( $options ): array {
		// Construct an empty array.
		$arr = [];

		foreach ( $options as $option_key => $option_val ) {
			// Build the config array for Google Analytics.
			if ( strpos( $option_key, 'ga_' ) !== false ) {
				$arr['google_analytics'][str_replace( 'ga_', '', $option_key )] = $option_val;
			}
		}

		return $arr;
	}

}

add_action(
	'init',
	function() {
		new \WP_Irving\Google_Analytics();
	}
);
