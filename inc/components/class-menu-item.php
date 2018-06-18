<?php
/**
 * Class file for Irving's Menu Item component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the components of the Menu Item.
 */
class Menu_Item extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'menu-item';

	/**
	 * Define a default config shape.
	 *
	 * @return array Default config.
	 */
	public function default_config() {
		/**
		 * Modify the default config for this component.
		 *
		 * @param  $defaults Component defaults.
		 */
		return apply_filters(
			'wp_irving_components_default_menu_item',
			[
				'label' => '',
				'url'   => '',
			]
		);
	}

	/**
	 * Parse a menu post.
	 *
	 * @param  WP_Post $menu_object Menu post object.
	 * @return Menu_Item An instance of the Menu_Item class.
	 */
	public function parse_menu_post( $menu_object ) {

		// Validate $menu_object.
		if ( ! $menu_object instanceof \WP_Post || 'nav_menu_item' !== $menu_object->post_type ) {
			return null;
		}

		// Determine label based on type.
		$label = ( 'custom' === $menu_object->type ) ? $menu_object->post_title : $menu_object->title;

		// Default fields.
		$this->set_config( 'label', $label );
		$this->set_config( 'url', $menu_object->url );

		/**
		 * Modify the config for the menu_item component using the menu object.
		 *
		 * @param  array    $this->config The config array for this component.
		 * @param  \WP_Post $menu_object  The menu post for this component.
		 */
		$this->config = apply_filters( 'wp_irving_components_config_menu_item', $this->config, $menu_object );

		return $this;
	}
}

/**
 * Helper to get the component.
 *
 * @param  string $name     Component name or array of properties.
 * @param  array  $config   Component config.
 * @param  array  $children Component children.
 * @return Menu_Item An instance of the Menu_Item class.
 */
function menu_item( $name = '', array $config = [], array $children = [] ) {
	return new Menu_Item( $name, $config, $children );
}
