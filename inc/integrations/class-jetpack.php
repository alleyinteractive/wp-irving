<?php
/**
 * WP Irving integration for Jetpack.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;
use WP_Irving\Components\Component;

/**
 * Jetpack.
 */
class Jetpack {
	use Singleton;

	/**
	 * Constructor for class.
	 */
	public function setup() {

		if ( function_exists( '\stats_build_view_data' ) ) {
			// Inject the Jetpack stats script into the Head component.
			add_filter( 'wp_irving_component_children', [ $this, 'inject_jetpack_stats_script_head_children' ], 10, 3 );
			// Inject the Site Stats component into the integrations config.
			add_filter( 'wp_irving_integrations_config', [ $this, 'inject_jetpack_stats_into_integrations_config' ] );
		}
	}

	/**
	 * Inject jetpack site stats script into the Head component.
	 *
	 * @param array  $children Children for this component.
	 * @param array  $config   Config for this component.
	 * @param string $name     Name of this component.
	 * @return array
	 */
	public function inject_jetpack_stats_script_head_children( array $children, array $config, string $name ): array {

		// Ony run this action on the `irving/head` in a `page` context.
		if (
			'irving/head' !== $name
			|| 'page' !== ( $config['context'] ?? 'page' )
		) {
			return $children;
		}

		return array_merge(
			$children,
			[
				new Component(
					'script',
					[
						'config' => [
							'src' => 'https://stats.wp.com/e-' . gmdate( 'YW' ) . '.js',
						],
					]
				),
			]
		);
	}

	/**
	 * Inject the JetpackSiteStats component into the integrations config.
	 *
	 * @param array $config The current configuration.
	 * @return array The updated configuration.
	 */
	public function inject_jetpack_stats_into_integrations_config( array $config ): array {

		// Bail early if the stats build function isn't available.
		if ( ! function_exists( '\stats_build_view_data' ) ) {
			return $config;
		}

		$data = \stats_build_view_data();

		if ( ! empty( $data ) ) {
			$config = array_merge(
				$config,
				[ 'jetpack_site_stats' => [ 'data' => $data ] ]
			);
		}

		return $config;
	}
}
