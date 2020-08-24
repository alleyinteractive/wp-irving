<?php
/**
 * WP Irving integration for Pico.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;
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

	public function verify_pico_user_for_sso( array $user ): array {
		// TODO: Dispatch a verification request to the Pico API. If the user
		// is verified, return the constructed user with an ID, email, and
		// username.
		// If the user isn't verified, return false, which will cause a failure
		// response to be returned on the front-end and the appropriate behavior
		// will be triggered.
		return $user;
	}
}
