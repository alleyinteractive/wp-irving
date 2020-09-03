<?php
/**
 * WP Irving integration for Google Tag Manager.
 *
 * @package WP_Irving;
 */

namespace WP_Irving\Integrations;

use WP_Irving\Components\Component;
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
	 * Setup the singleton.
	 */
	public function setup() {
		// Retrieve any existing integrations options.
		$this->options = get_option( 'irving_integrations' );

		// Register settings fields for integrations.
		add_action( 'admin_init', [ $this, 'register_settings_fields' ] );

		if ( ! is_admin() ) {
			add_filter( 'wp_irving_component_children', [ $this, 'inject_gtm_tags_into_head_children' ], 10, 3 );
		}
	}

	/**
	 * Parse GTM's head markup, inject into the Head component.
	 *
	 * @param array  $children Children for this component.
	 * @param array  $config   Config for this component.
	 * @param string $name     Name of this component.
	 * @return array
	 */
	public function inject_gtm_tags_into_head_children( array $children, array $config, string $name ): array {
		// Ony run this action on the `irving/head` in a `page` context.
		if (
			'irving/head' !== $name
			|| 'page' !== ( $config['context'] ?? 'page' )
		) {
			return $children;
		}

		$data_layer = [
			'event' => 'irving.page',
		];

		/**
		 * Filters the data layer provided to GTM for each page.
		 *
		 * @param array $data_layer The data layer arguments for GTM.
		 */
		$data_layer = apply_filters( 'wp_irving_gtm_data_layer', $data_layer );

		$children[] = new Component(
			'script',
			[
				'config'   => [
					'id'   => 'gtm-irving-page',
					'type' => 'text/javascript',
				],
				'children' => [
					'window.dataLayer = window.dataLayer ?? [];',
					'window.dataLayer.push(' . wp_json_encode( $data_layer ) . ');',
				],
			]
		);

		return $children;
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
