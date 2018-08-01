<?php
/**
 * Class file for Irving's Term component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Term component.
 */
class Term extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'term';

	/**
	 * Define the default config of a header.
	 *
	 * @return array Default config values for this component.
	 */
	public function default_config() {
		return [
			'id'   => 0,
			'name' => '',
			'slug' => '',
			'link' => '',
		];
	}

	/**
	 * Set the term.
	 *
	 * @param  WP_Term|int $term     Term object or id.
	 * @param  string      $taxonomy Taxonomy for this term.
	 * @return Content_Item|null An instance of this component.
	 */
	public function set_term( $term, $taxonomy = '' ) {
		// $term is a term ID.
		if ( ! $term instanceof \WP_Term && 0 !== absint( $term ) ) {
			$term = get_term( absint( $term ), $taxonomy );
		}

		// $term is a valid WP_Term object.
		if ( $term instanceof \WP_Term ) {
			$this->set_config( 'id', $term->term_id );
			$this->set_config( 'name', $term->name );
			$this->set_config( 'slug', $term->slug );
			$this->set_config( 'link', get_term_link( $term ) );
		} else {
			return null;
		}

		return $this;
	}

	/**
	 * Get an array of Term components from an array of terms or term ids.
	 *
	 * @return array Term components.
	 */

	/**
	 * Get an array of Term components from an array of terms or term ids.
	 *
	 * @param  array  $terms    Array of WP_Terms or term ids.
	 * @param  string $taxonomy Taxonomy slug, required when $terms is an array
	 *                          of ids.
	 * @return array Term components.
	 */
	public static function get_term_components( array $terms, $taxonomy = '' ) {
		return array_filter(
			array_map(
				function( $term ) use ( $taxonomy ) {
					return ( new Term() )->set_term( $term, $taxonomy );
				},
				$terms
			)
		);
	}
}
