<?php
/**
 * Trait for defining a content item.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Trait for a content item.
 */
trait Content_Item {

	/**
	 * Default shape of a Content Item.
	 *
	 * @return array Shape of a content item.
	 */
	public function default_shape() {
		return [
			'title' => '',
		];
	}

	/**
	 * Set this content item to a post.
	 *
	 * @param  null|int|WP_Post $post Post ID, WP_Post object, or null.
	 * @return instance of this class.
	 */
	public function set_to_post( $post = null ) {

		// If null, attempt to get a post ID from the loop.
		if ( is_null( $post ) ) {
			$post = get_the_ID();
		}

		// If post is an integer, assume it's the post ID.
		if ( ! $post instanceof \WP_Post && 0 !== absint( $post ) ) {
			$post = get_post( $post );
		}

		// Valid post object. Map fields.
		if ( $post instanceof \WP_Post ) {
			if ( method_exists( $this, 'parse_post' ) ) {
				$this->parse_post( $post );
			}
		}

		return $this;
	}

	/**
	 * Conform a post to the shape of a content item.
	 *
	 * @param  \WP_Post $post WP_Post object.
	 * @return instance of this class.
	 */
	public function parse_post( \WP_Post $post ) {
		$this->set_config( 'type', 'post' );
		$this->set_config( 'title', $post->post_title );

		$this->config = wp_parse_args( $this->config, $this->default_shape() );

		return $this;
	}

	/**
	 * Set this content item to a term.
	 *
	 * @todo  Add more robust term setting logic. Alias get_term_by() more
	 * appropriately.
	 *
	 * @param  WP_Term|integer $term     WP_Term object.
	 * @param  string          $taxonomy Taxonomy to get the object.
	 * @return instance of this class.
	 */
	public function set_to_term( $term, string $taxonomy = '' ) {

		// We didn't pass a term, so assume term_id.
		if ( ! $term instanceof \WP_Term ) {
			$term = get_term_by( 'id', $term, $taxonomy );
		}

		// Valid post object. Map fields.
		if ( $term instanceof \WP_Term ) {
			if ( method_exists( $this, 'parse_term' ) ) {
				$this->parse_term( $term );
			}
		}

		return $this;
	}

	/**
	 * Default method for converting a term into a content item shape.
	 *
	 * @param  \WP_Term $term WP_Term object.
	 * @return instance of this class.
	 */
	public function parse_term( \WP_Term $term ) {
		$this->set_config( 'type', 'term' );
		$this->set_config( 'title', $term->name );

		$this->config = wp_parse_args( $this->config, $this->default_shape() );

		return $this;
	}
}
