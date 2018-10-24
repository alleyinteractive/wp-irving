<?php
/**
 * Class file for the Social Item component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Social Item component.
 */
class Social_Links extends Component {

	use \WP_Irving\Social;

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'social-links';

	/**
	 * Array of services.
	 *
	 * @var string
	 */
	public static $services = [
		'facebook',
		'twitter',
		'linkedin',
		'pinterest',
		'whatsapp',
	];

	/**
	 * Define a default config shape.
	 *
	 * @return array Default config.
	 */
	public function default_config() {
		return [
			'service_labels' => [
				'facebook'  => __( 'Facebook', 'wp-irving' ),
				'twitter'   => __( 'Twitter', 'wp-irving' ),
				'linkedin'  => __( 'LinkedIn', 'wp-irving' ),
				'pinterest' => __( 'Pinterest', 'wp-irving' ),
				'whatsapp'  => __( 'WhatsApp', 'wp-irving' ),
			],
			'display_icons'  => true,
		];
	}

	/**
	 * Retrieve service labels for use in custom fields.
	 *
	 * @return array Array of services with labels.
	 */
	public function get_service_labels() {
		return array_filter( $this->get_config( 'service_labels' ), function ( $key ) {
			return in_array( $key, self::$services );
		}, ARRAY_FILTER_USE_KEY );
	}
}
