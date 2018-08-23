<?php
/**
 * Class file for Irving's Head component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Head component.
 */
class Head extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'head';

	/**
	 * Define the default config of a head.
	 *
	 * @return array A default config.
	 */
	public function default_config() {
		return [];
	}

	/**
	 * Set a title tag as a child of the Head component.
	 *
	 * @param string $value The title value.
	 * @return Head
	 */
	public function set_title( $value ) {
		$title_component = ( new Component( 'title' ) )
			->set_children( [ $value ] );
		foreach ( $this->children as $index => $component ) {
			if ( 'title' === $component->name ) {
				$this->children[ $index ] = $title_component;
				return $this;
			}
		}

		return $this->set_children( [ $title_component ], true );
	}

	/**
	 * Add a meta tag as a child of the Head component.
	 *
	 * @param string $property The meta property attribute.
	 * @param string $content  The meta content attribute.
	 * @return Head
	 */
	public function add_meta( $property, $content ) {
		return $this->set_children([
			( new Component( 'meta' ) )
				->set_config( 'property', $property )
				->set_config( 'content', $content ),
		]);
	}
}
