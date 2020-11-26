<?php
/**
 * WP Irving integration for Parse.ly.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Components;
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
		// Ensure Parse.ly plugins exists and is enabled.
		if ( ! defined( 'PARSELY_VERSION' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			// Parse Parse.ly's head markup and inject it into the Head component.
			add_filter( 'wp_irving_component_children', [ $this, 'inject_parsely_tags_into_head_children' ], 10, 3 );
		}
	}

	/**
	 * Parse Parse.ly's head markup, inject into the Head component.
	 *
	 * @param array  $children Children for this component.
	 * @param array  $config   Config for this component.
	 * @param string $name     Name of this component.
	 * @return array
	 */
	public function inject_parsely_tags_into_head_children( array $children, array $config, string $name ): array {
		// Ony run this action on the `irving/head` in a `page` context.
		if (
			'irving/head' !== $name
			|| 'page' !== ( $config['context'] ?? 'page' )
		) {
			return $children;
		}
		return array_merge(
			$children,
			Components\html_to_components( $this->get_parsely_site_markup(), [ 'meta', 'script' ] )
		);
	}

	/**
	 * Capture the markup output by Parse.ly in the site <head>.
	 *
	 * @return string
	 */
	public function get_parsely_site_markup(): string {
		global $parsely;
		ob_start();
		$parsely->insert_parsely_page();
		return ob_get_clean();
	}
}
