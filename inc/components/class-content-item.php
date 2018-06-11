<?php
/**
 * Class file for Irving's Content Item component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Content_Item component.
 */
class Content_Item extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'content-item';

	/**
	 * Define the default config of a content item.
	 *
	 * @return Content_Item An instance of this component.
	 */
	public function default_config() {
		return [
			'type'   => '',
			'object' => null,
		];
	}
}

/**
 * Helper to get the component.
 *
 * @param  string $name     Component name or array of properties.
 * @param  array  $config   Component config.
 * @param  array  $children Component children.
 * @return Content_Item An instance of the Content_Item class.
 */
function content_item( $name = '', array $config = [], array $children = [] ) {
	return new Content_Item( $name, $config, $children );
}
