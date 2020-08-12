<?php
/**
 * WP Irving integration for Jetpack.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Components;
use WP_Irving\Component;

/**
 * Jetpack.
 */
class Jetpack {

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
	 * Constructor for class.
	 */
	public function setup() {

        // if ( function_exists( '\stats_build_view_data' ) ) {
            // Inject the Jetpack stats script into the Head component.
            add_filter( 'wp_irving_component_children', __NAMESPACE__ . '\Jetpack_Site_Stats\inject_jetpack_stats_script_head_children', 10, 3 );
        // }
	}

}
