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
	 * The option key for the integration.
	 *
	 * @var string
	 */
	private $option_key = 'parsely';

	/**
	 * Setup the integration.
	 */
	public function setup() {
		// Ensure Parse.ly plugins exists and is enabled.
		if ( ! defined( 'PARSELY_VERSION' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			// Parse Parse.ly's head JSON-LD markup and inject it into the Head component.
			add_filter( 'wp_irving_component_children', [ $this, 'inject_parsely_tags_into_head_children' ], 10, 3 );

			// Add Parse.ly's site ID to the props.
			add_filter( 'wp_irving_integrations_option', [ $this, 'inject_parsely' ] );
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

	/**
	 * Inject Parse.ly props into the integrations manager option.
	 *
	 * @param array $options Integrations option array.
	 * @return array Updated options.
	 */
	public function inject_parsely( array $options ): array {
		$parsely_options = get_option( 'parsely' );
		if ( empty( $parsely_options ) || empty( $parsely_options['apikey'] ) ) {
			return $options;
		}
		// Get and validate the site.
		$options[ $this->option_key ] = [
			'site' => $parsely_options['apikey'],
		];
		return $options;
	}
}
