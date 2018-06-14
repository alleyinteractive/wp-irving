<?php
/**
 * Class file for Irving's Image component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the components of the image.
 */
class Image extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'image';

	/**
	 * Define the default config of an image.
	 *
	 * @return array Default config.
	 */
	public function default_config() {
		return [
			'alt'    => '',
			'src'    => '',
			'srcset' => '',
			'url'    => '',
		];
	}
}

/**
 * Helper to get the component.
 *
 * @param  string $name     Component name or array of properties.
 * @param  array  $config   Component config.
 * @param  array  $children Component children.
 * @return Image An instance of the Image class.
 */
function image( $name = '', array $config = [], array $children = [] ) {
	return new Image( $name, $config, $children );
}
