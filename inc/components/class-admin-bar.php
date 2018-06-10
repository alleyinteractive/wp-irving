<?php
/**
 * Class file for Irving's Admin Bar component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the components of the admin bar.
 */
class Admin_Bar extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'admin-bar';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->children = [
			$this->create_button(
				[
					'label' => __( 'Dashboard', 'wp-irving' ),
					'url'   => admin_url(),
				]
			),
			$this->create_button(
				[
					'label' => __( 'New', 'wp-irving' ),
				],
				[
					$this->create_button(
						[
							'label' => __( 'Post', 'wp-irving' ),
							'url'   => admin_url( 'post-new.php' ),
						]
					),
					$this->create_button(
						[
							'label' => __( 'Page', 'wp-irving' ),
							'url'   => admin_url( 'post-new.php?post_type=page' ),
						]
					),
					$this->create_button(
						[
							'label' => __( 'User', 'wp-irving' ),
							'url'   => admin_url( 'user-new.php' ),
						]
					),
				]
			),
		];
	}

	/**
	 * Modifies the component based on the API query.
	 *
	 * @param  WP_Query $wp_query WP_Query object.
	 * @return Admin_Bar An instance of this component.
	 */
	public function parse_query( $wp_query ) {
		// Add a button to edit the current post.
		if ( $wp_query->is_single() || $wp_query->is_page() ) {
			$this->children[] = $this->create_button(
				[
					'label' => __( 'Edit', 'wp-irving' ),
					'url'   => get_edit_post_link( $wp_query->posts[0]->ID ),
				]
			);
		}

		return $this;
	}

	/**
	 * Define the default config of an admin bar button.
	 *
	 * @return Admin_Bar An instance of this component.
	 */
	public function default_button_config() {
		return [
			'icon'  => '',
			'label' => '',
			'url'   => '',
		];
	}

	/**
	 * Create an instance of admin-bar-button.
	 *
	 * @param array $button_config   Config for the button.
	 * @param array $button_children Children for the button.
	 * @return array Admin Bar Button component.
	 */
	public function create_button( array $button_config = array(), array $button_children = array() ) {

		// Clean config.
		$button_config = wp_parse_args( $button_config, $this->default_button_config() );

		return [
			'name'     => 'admin-bar-button',
			'config'   => $button_config,
			'children' => $button_children,
		];
	}
}

/**
 * Helper to get the admin bar component.
 *
 * @param  string $name     Component name or array of properties.
 * @param  array  $config   Component config.
 * @param  array  $children Component children.
 * @return Admin_Bar An instance of the Admin_Bar class.
 */
function admin_bar( $name = '', array $config = [], array $children = [] ) {
	return new Admin_Bar( $name, $config, $children );
}
