<?php
/**
 * WP Irving integration for Pico.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Components\Component;
use Pico_Setup;
use Pico_Widget;

/**
 * Class to integrate Pico with Irving.
 */
class Pico {

	/**
	 * Class instance.
	 *
	 * @var null|self
	 */
	protected static $instance;

	/**
	 * Get class instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Setup the singleton. Validate JWT is installed, and setup hooks.
	 */
	public function setup() {

		// Ensure Pico exists and is enabled.
		if ( ! defined( 'PICO_VERSION' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			add_filter( 'irving_integrations_option', [ $this, 'inject_pico_configuration' ] );
		}
	}

	/**
	 * Modify the `irving_integrations` options to include pico.
	 *
	 * @param array $options Integrations options key.
	 * @return array Updated options array.
	 */
	public function inject_pico_configuration( $options ): array {

		$options['pico'] = [
			'publisher_id' => Pico_Setup::get_publisher_id(),
			'page_info'    => Pico_Widget::get_current_view_info(),
		];

		return $options;
	}
}
