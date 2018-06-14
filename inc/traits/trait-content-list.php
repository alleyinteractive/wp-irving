<?php
/**
 * Trait for defining a content list.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Trait for a content list.
 */
trait Content_List {

	/**
	 * Set this content list to a post.
	 *
	 * @param  array $post_ids Array of post ids.
	 * @return instance of this class.
	 */
	public function set_children_by_post_ids( array $post_ids ) {

		$this->children = array_map( function( $post_id ) {
			return \WP_Irving\Component\content_card()->set_to_post( $post_id );
		}, $post_ids );

		return $this;
	}
}
