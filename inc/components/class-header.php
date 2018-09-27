<?php
/**
 * Class file for Irving's Header component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Header component.
 */
class Header extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'header';

	/**
	 * Define the default config of a header.
	 *
	 * @return Header An instance of this component.
	 */
	public function default_config() {
		return [
			'siteTitle' => get_bloginfo( 'name' ),
			'siteUrl'   => site_url(),
		];
	}
}
