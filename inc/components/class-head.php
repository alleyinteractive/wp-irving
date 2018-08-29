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
	 * Initalize this component.
	 */
	public function __construct() {
		parent::__construct();

		// Set the default title.
		$this->set_title( get_bloginfo( 'name' ) );
	}

	/**
	 * Define the default config of a head.
	 *
	 * @return array A default config.
	 */
	public function default_config() {
		return [];
	}

	/**
	 * Set the state of this component using a $wp_query object.
	 *
	 * @todo  Clean up this function and make it more verbose.
	 *
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return Head
	 */
	public function set_from_query( $wp_query ) {
		if ( $wp_query->is_single() ) {
			$this->set_title( $wp_query->post->post_title );
		}

		return $this;
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

		// Loop through children to replace an existing child component.
		foreach ( $this->children as $index => $component ) {
			// Update title.
			if ( 'title' === $component->name ) {
				$this->children[ $index ] = $title_component;
				return $this;
			}
		}

		// Set new title.
		return $this->set_children( [ $title_component ], true );
	}

	/**
	 * Helper function for setting a canonical url.
	 *
	 * @param  string $url Canonical URL.
	 * @return Head
	 */
	public function set_canonical_url( $url ) {
		return $this->add_link( 'canonical', $url );
	}

	/**
	 * Helper function for adding a new meta tag.
	 *
	 * @param string $property Property value.
	 * @param string $content  Content value.
	 * @return Head
	 */
	public function add_meta( $property, $content ) {
		return $this->add_tag(
			'meta',
			[
				'property' => $property,
				'content'  => $content,
			]
		);
	}

	/**
	 * Helper function for adding a new link tag.
	 *
	 * @param string $rel  Rel value.
	 * @param string $href Href value.
	 * @return Head
	 */
	public function add_link( $rel, $href ) {
		return $this->add_tag(
			'link',
			[
				'rel'  => $rel,
				'href' => $href,
			]
		);
	}

	/**
	 * @param $src
	 * @param bool $defer
	 * @param bool $async
	 *
	 * @return Head
	 */
	public function add_script( $src, $defer = true, $async = true ) {
		return $this->add_tag(
			'script',
			[
				'src' => $src,
				'defer' => $defer,
				'async' => $async,
			]
		);
	}

	/**
	 * Helper function to quickly add a new tag.
	 *
	 * @param  string $tag        Tag value.
	 * @param  array  $attributes Tag attributes.
	 * @return Head
	 */
	protected function add_tag( $tag, $attributes = [] ) {

		// Initalize a new component for this tag.
		$component = new Component( $tag );

		// Add all attributes.
		foreach ( $attributes as $key => $value ) {
			$component->set_config( $key, $value );
		}

		// Append this tag as a child component.
		return $this->set_children(
			[
				$component,
			],
			true
		);
	}
}
