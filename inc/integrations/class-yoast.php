<?php
/**
 * WP Irving integration for Yoast SEO.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\Utils;

/**
 * Yoast.
 */
class Yoast {

	/**
	 * Constructor for class.
	 */
	public function __construct() {

		// Ensure Yoast exists and is enabled.
		if ( ! class_exists( '\WPSEO_Frontend' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			// Parse Yoast's head markup and inject it into the Head component.
			add_filter( 'wp_irving_page_head_component_children', [ $this, 'inject_yoast_tags_into_head_children' ] );
		}
	}

	/**
	 * Parse Yoast's head markup, inject into the Head component.
	 *
	 * @param array $children Children for the <head>.
	 * @return array
	 */
	public function inject_yoast_tags_into_head_children( array $children ): array {
		return array_merge(
			$children,
			\WP_Irving\Utils\html_to_components( $this->get_yoasts_head_markup(), [ 'title', 'meta', 'link' ] )
		);
	}

	/**
	 * Capture the markup output by Yoast in the site <head>.
	 *
	 * @return string
	 */
	public function get_yoasts_head_markup(): string {
		ob_start();
		\do_action( 'wpseo_head' ); // phpcs:ignore
		return ob_get_clean();
	}
}

add_action(
	'init',
	function() {
		new \WP_Irving\Yoast();
	}
);
