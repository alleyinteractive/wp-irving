<?php
/**
 * Class file for Irving's Footer component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Footer component.
 */
class Footer extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'footer';

	/**
	 * Define the default config of a footer.
	 *
	 * @return Footer An instance of this component.
	 */
	public function default_config() {
		return [
			'content' => '',
		];
	}
}

/**
 * Helper to get the component.
 *
 * @param  string $name     Component name or array of properties.
 * @param  array  $config   Component config.
 * @param  array  $children Component children.
 * @return Footer An instance of the Footer class.
 */
function footer( $name = '', array $config = [], array $children = [] ) {
	return new Footer( $name, $config, $children );
}
