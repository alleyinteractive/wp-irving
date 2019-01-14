<?php
/**
 * Class file for Irving's Pagination Link component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Pagination_Link component.
 */
class Pagination_Link extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'pagination-link';

	/**
	 * Define the default config for this component.
	 *
	 * @return array
	 */
	public function default_config() {
		return [
			'current' => false,
			'text'    => '',
			'url'     => '',
		];
	}
}
