<?php
/**
 * Class file for the Parsely component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Parsely component.
 */
class Parsely extends Component {

	/**
	 * Name of site option associated with parsely
	 *
	 * @var string
	 */
	public static $option_field = 'parsely_site';

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'parsely';

	/**
	 * Define the default config for Parsely
	 *
	 * @return array Default config values for this component.
	 */
	public function default_config() {
		return [
			'site' => $this->get_parsely_site(),
		];
	}

	/**
	 * Initialize parsely
	 */
	public function setup_head() {
		add_action( 'wp_irving_head', [ $this, 'setup_parsely_meta' ], 10, 2 );
	}

	/**
	 * Get Forum shortname for Parsely embeds
	 *
	 * @return array Filtered value for disqus forum shortname
	 */
	public function get_parsely_site() {
		return apply_filters( 'wp_irving_parsely_site', get_option( self::$option_field ) );
	}

	/**
	 * Add Parsely meta tags.
	 *
	 * @param \WP_Irving\Component\Head $head     Head object.
	 * @param \WP_Query                 $wp_query WP_Query object.
	 */
	public function setup_parsely_meta( $head, $wp_query ) {
		// Add Parsely meta tags.
		if ( $wp_query->is_single() && ! empty( $wp_query->post ) ) {

			// Get meta values.
			$parsely_meta = $this->get_parsely_meta( $wp_query->post );

			// Apply meta values.
			foreach ( $parsely_meta as $name => $content ) {
				$head->add_tag(
					'meta',
					[
						'name'    => $name,
						'content' => $content,
					]
				);
			}
		}
	}

	/**
	 * Helper function that returns all the attributes needed for Parsely on a
	 * single article.
	 *
	 * @param  \WP_Post $post Post object.
	 * @return array
	 */
	public function get_parsely_meta( \WP_Post $post ) : array {

		// Base properties.
		$meta['parsely-link']    = get_the_permalink( $post );
		$meta['parsely-post-id'] = $post->ID;
		$meta['parsely-pub']     = get_the_time( 'c', $post );
		$meta['parsely-title']   = get_the_title( $post );
		$meta['parsely-type']    = 'article';
		$meta['parsely-tags']    = $this->get_parsely_tags_string( $post );
		$meta['parsely-author']  = $this->get_parsely_authors_string( $post );

		// Featured image.
		if ( has_post_thumbnail( $post ) ) {
			$meta['parsely-image'] = get_the_post_thumbnail_url( $post, 'full' );
		}

		return $meta;
	}

	/**
	 * Get a string of all authors for a given post.
	 *
	 * @param  \WP_Post $post Post object.
	 * @return string
	 */
	public function get_parsely_authors_string( \WP_Post $post ) : string {
		if ( ! function_exists( 'get_coauthors' ) ) {
			return get_the_author_meta( 'display_name', $post->post_author ) ?? '';
		}

		// Get co-authors.
		$coauthors = (array) get_coauthors( $post->ID );

		// Get all display names.
		$display_names = array_map(
			function( $coauthor ) {
				return $coauthor->display_name ?? '';
			},
			$coauthors
		);

		// Clean values.
		$display_names = array_filter( $display_names );
		if ( empty( $display_names ) ) {
			return '';
		}

		return implode( ',', $display_names );
	}

	/**
	 * Get a string of all category slugs for a given post.
	 *
	 * @param  \WP_Post $post Post object.
	 * @return string
	 */
	public function get_parsely_tags_string( \WP_Post $post ) : string {
		// Build tags string.
		$tags = [];

		$categories = wp_get_object_terms( $post->ID, 'category' );
		foreach ( $categories as $category ) {
			$tags[] = $category->slug;
		}

		// Return empty string if needed.
		if ( empty( $tags ) ) {
			return '';
		}

		// Return comma-delimited tags.
		return implode( ',', array_unique( $tags ) );
	}
}
