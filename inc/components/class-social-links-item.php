<?php
/**
 * Class file for the Social Links Item component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Social Links Item component.
 */
class Social_Links_Item extends Component {
	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'social-links-item';

	/**
	 * Define a default config shape.
	 *
	 * @return array Default config.
	 */
	public function default_config() {
		return [
			'type'        => '',
			'url'         => '',
			'displayIcon' => true,
		];
	}

	/**
	 * Set the class data.
	 *
	 * @param array $data The data to set.
	 */
	public function set_data( $data ) {
		$this->config = wp_parse_args( $data, $this->default_config() );
	}
}
