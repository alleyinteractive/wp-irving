<?php
/**
 * WP Irving integration for Google AMP.
 *
 * @package WP_Irving;
 */

namespace WP_Irving;

/**
 * Class to integrate Google AMP with Irving.
 */
class Google_AMP {

	/**
	 * Constructor for class.
	 */
	public function __construct() {

		// Ensure the AMP plugin exists and is enabled.
        if ( ! function_exists( 'is_amp_endpoint' ) ) {
            return;
        }
        
        // Hook into template redirect to render AMP template.
        add_action( 'template_redirect', [ $this, 'amp_template_redirect' ], 9 );
	}

	/**
	 * Determine if this is an AMP endpoint, and if so, call the render
	 * function directly. The AMP plugin does not call it on an early enough
	 * hook for us.
	 */
	public function amp_template_redirect() {
		if ( is_amp_endpoint() ) {
			amp_render();
		}
	}
}

add_action( 'init', function() {
	new \WP_Irving\Google_AMP();
} );
