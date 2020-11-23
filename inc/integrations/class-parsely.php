<?php
/**
 * WP Irving integration for Parse.ly.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;

/**
 * Class to integrate Parsely with Irving.
 */
class Parsely {
	use Singleton;

	/**
	 * Setup the integration.
	 */
	public function setup() {
		// Ensure Pico exists and is enabled.
		if ( ! defined( 'PICO_VERSION' ) ) {
			return;
		}
	}
}
