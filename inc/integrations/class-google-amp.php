<?php
/**
 * WP Irving integration for Google AMP.
 *
 * @package WP_Irving;
 */

namespace WP_Irving\Integrations;

use WP_Irving\Components;
use WP_Irving\Singleton;

/**
 * Class to integrate Google AMP with Irving.
 */
class Google_AMP {
	use Singleton;

	/**
	 * Constructor for class.
	 */
	public function setup() {

		// Ensure the AMP plugin exists and is enabled.
		if ( ! function_exists( 'is_amp_endpoint' ) ) {
			return;
		}

		// Hook into template redirect to render AMP template.
		add_action( 'template_redirect', [ $this, 'amp_template_redirect' ], 9 );

		// Automatically add the correct <link ref="amphtml">.
		add_action( 'wp_irving_component_children', [ $this, 'inject_link_to_amp' ], 10, 3 );
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

	/**
	 * Capture the markup output by the AMP plugin for the amphtml <link>.
	 *
	 * @return string
	 */
	public function get_amphtml_link_markup(): string {
		if ( ! function_exists( 'amp_add_amphtml_link' ) ) {
			return '';
		};

		ob_start();
		amp_add_amphtml_link();
		return trim( ob_get_clean() );
	}

	/**
	 * Inject the <link ref="amphtml" /> tag into the <head> component.
	 *
	 * @param array  $children Children for this component.
	 * @param array  $config   Config for this component.
	 * @param string $name     Name of this component.
	 * @return array
	 */
	public function inject_link_to_amp( array $children, array $config, string $name ): array {
		// Ony run this action on the `irving/head` in a `page` context.
		if (
			'irving/head' !== $name
			|| 'page' !== ( $config['context'] ?? 'page' )
		) {
			return $children;
		}

		return array_merge(
			$children,
			Components\html_to_components( $this->get_amphtml_link_markup(), [ 'link' ] )
		);
	}
}
