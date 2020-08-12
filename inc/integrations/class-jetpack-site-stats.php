<?php
/**
 * WP Irving component for Jetpack Site Stats.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Components\Component;

/**
 * Jetpack Site Stats integration.
 */
class Jetpack_Site_Stats extends \WP_Components\Component {

	use \WP_Components\WP_Query;

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'jetpack-site-stats';

	/**
	 * Define a default config.
	 *
	 * @return array Default config.
	 */
	public function default_config(): array {
		return [
			'data' => [
				'blog' => 0,
				'post' => 0,
			],
		];
	}

	/**
	 * Hook into query being set.
	 *
	 * @return \WP_Components\Component
	 */
	public function query_has_set(): self {
		// Bail early if the \stats_build_view_data function does not exist.
		if ( ! function_exists( '\stats_build_view_data' ) ) {
			$this->set_invalid();

			return $this;
        }

        // Build the data.
		$data = \stats_build_view_data();
		$this->set_config( 'data', $data );

		return $this;
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
                )
            ]
		);
	}
}
