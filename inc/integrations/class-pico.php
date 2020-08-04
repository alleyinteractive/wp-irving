<?php
/**
 * WP Irving integration for Pico.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\Components\Component;
use Pico_Setup;
use Pico_Widget;

/**
 * Pico.
 */
class Pico {

	/**
	 * Constructor for class.
	 */
	public function __construct() {

		// Ensure Yoast exists and is enabled.
		if ( ! defined( 'PICO_VERSION' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			add_filter( 'wp_irving_components_route', __NAMESPACE__ . '\\setup_pico', 10, 4 );
		}
	}
}

function setup_pico(
	array $data,
	\WP_Query $query,
	string $context,
	string $path
): array {

	// Unshift a `irving/pico` component to the top of the `page` array.
	array_unshift(
		$data['page'],
		new Component(
			'irving/pico',
			[
				'config' => [
					'publisher_id' => Pico_Setup::get_publisher_id(),
					'page_info'    => Pico_Widget::get_current_view_info(),
				],
			]
		)
	);

	return $data;
}

add_action(
	'init',
	function() {
		new \WP_Irving\Pico();
	}
);
