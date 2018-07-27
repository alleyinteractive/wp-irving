<?php
/**
 * Class file for Irving's HTML component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the HTML component.
 */
class HTML extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'html';

	/**
	 * Define the default config of a header.
	 *
	 * @return array
	 */
	public function default_config() {
		return [
			'content' => '',
		];
	}
}
