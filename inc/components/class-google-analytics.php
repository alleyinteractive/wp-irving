<?php
/**
 * Class file for the Google Analytics component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Google Analytics component.
 */
class Google_Analytics extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'google-analytics';

	/**
	 * Initalize this component.
	 */
	public function __construct() {
		parent::__construct();

		// Easily set the tracking ID using this filter.
		$this->set_config(
			'tracking_id',
			apply_filters(
				'wp_irving_component_set_google_analytics_tracking_id',
				$this->get_config( 'tracking_id' )
			)
		);
	}

	/**
	 * Define a default config shape.
	 *
	 * @return array Default config.
	 */
	public function default_config() {
		return [
			'tracking_id' => '',
		];
	}
}
