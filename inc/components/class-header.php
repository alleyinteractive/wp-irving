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
			'site_title' => get_bloginfo( 'name' ),
			'site_url'   => site_url(),
		];
	}
}

/**
 * Helper to get the component.
 *
 * @param  string $name     Component name or array of properties.
 * @param  array  $config   Component config.
 * @param  array  $children Component children.
 * @return Header An instance of the Header class.
 */
function header( $name = '', array $config = [], array $children = [] ) {
	return new Header( $name, $config, $children );
}
