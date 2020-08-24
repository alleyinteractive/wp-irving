<?php
/**
 * WP Irving Integrations Manager.
 *
 * Register a settings page that manages integration key/value pairs to be sent
 * to the front end using the components API.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;

/**
 * Class for managing integrations in Irving.
 */
class Integrations_Manager {
	use Singleton;

	/**
	 * Register the manager's page and settings fields.
	 */
	public function setup() {
		// Register admin page.
		add_action( 'admin_menu', [ $this, 'register_admin' ] );

		// Register settings fields for integrations.
		add_action( 'admin_init', [ $this, 'register_settings_fields' ] );

		// Filter the updated option values prior to submission.
		add_filter( 'pre_update_option_irving_integrations', [ $this, 'group_and_format_options_for_storage' ] );
	}

	/**
	 * Register settings fields for display.
	 */
	public function register_settings_fields() {
		// Register a new setting for the integrations manager to consume/set.
		register_setting( 'wp_irving_integrations', 'irving_integrations' );
		// Register the section.
		add_settings_section(
			'irving_integrations_settings',
			__( 'Add keys for integrations.', 'wp-irving' ),
			'',
			'wp_irving_integrations'
		);
	}

	/**
	 * Loop through the updated options, group them by their integration's key,
	 * and remove any prefix set by the option's input.
	 *
	 * @param array $options The updated options.
	 * @return array The formatted options.
	 */
	public function group_and_format_options_for_storage( array $options ): array {
		$formatted_options = [];

		foreach ( $options as $key => $val ) {
			switch ( $key ) {
				// Build the config array for GA.
				case strpos( $key, 'ga_' ) !== false:
					$formatted_options['google_analytics'][ str_replace( 'ga_', '', $key ) ] = $val;
					break;
				// Build the contig array for GTM.
				case strpos( $key, 'gtm_' ) !== false:
					$formatted_options['google_tag_manager'][ str_replace( 'gtm_', '', $key ) ] = $val;
		 			break;
				// Build the config array for Coral.
				case strpos( $key, 'coral_' ) !== false:
					$formatted_options['coral'][ str_replace( 'coral_', '', $key ) ] = $val;
					// Set the options to private. This will prevent them from being passed through
					// the components JSON endpoint.
					$formatted_options['coral']['private'] = true;
					break;
				default:
					$formatted_options[ $key ] = $val;
			}
		}

		return $formatted_options;
	}

	/**
	 * Render the admin page in the settings submenu.
	 */
	public function register_admin() {
		add_submenu_page(
			'options-general.php',
			esc_html__( 'Irving Integrations', 'wp-irving' ),
			esc_html__( 'Irving Integrations', 'wp-irving' ),
			'manage_options',
			'wp-irving-integrations',
			[ $this, 'render' ]
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render() {
		// Check if the user have submitted the settings.
		if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore
			add_settings_error(
				'irving_integrations_messages',
				'irving_integrations_message',
				esc_html__( 'Settings Saved', 'wp-irving' ),
				'updated'
			);
		}

		?>
			<div class="wrap">
				<h1 class="wp-heading-inline">
					<?php esc_html_e( 'WP Irving - Integrations Manager', 'wp-irving' ); ?>
				</h1>

				<hr class="wp-header-end">

				<form method="post" action="options.php">
					<?php settings_fields( 'wp_irving_integrations' ); ?>
					<?php do_settings_sections( 'wp_irving_integrations' ); ?>
					<?php submit_button( __( 'Save Settings', 'wp-irving' ) ); ?>
				</form>
			</div>
		<?php
	}
}
