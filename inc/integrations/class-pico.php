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
	 * Setup the integration.
	 */
	public function setup() {

		// Ensure Pico exists and is enabled.
		if ( ! defined( 'PICO_VERSION' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			// Filter the integrations manager to include our Pico props.
			add_filter( 'irving_integrations_option', [ $this, 'inject_pico' ] );

			// Wrap content with `<div id="pico"></div>`.
			add_filter( 'the_content', [ 'Pico_Widget', 'filter_content' ] );
		}
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
		$options['pico']['page_info']['taxonomies'] = (object) $options['pico']['page_info']['taxonomies'];

		return $options;
	}
}
