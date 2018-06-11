<?php
/**
 * Class file for Irving's Content List component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Content_List component.
 */
class Content_List extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'content-list';

	/**
	 * Define the default config of a content list.
	 *
	 * @return Content_List An instance of this component.
	 */
	public function default_config() {
		return [
			'layout' => '',
			'title'  => '',
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
 * @return Content_List An instance of the Content_List class.
 */
function content_list( $name = '', array $config = [], array $children = [] ) {
	return new Content_List( $name, $config, $children );
}
