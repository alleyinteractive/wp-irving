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
	 * Set the state of this component using a $wp_query object.
	 *
	 * @todo  Clean up this function and make it more verbose.
	 *
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return Head
	 */
	public function set_from_query( $wp_query ) {

		// Set default title.
		$this->set_title( get_bloginfo( 'name' ) );

		if ( $wp_query->is_404() ) {
			$this->set_title( __( '404 - Page not found', 'wp-irving' ) );
		} elseif ( $wp_query->is_search() ) {
			$title = sprintf(
				/* translators: search term */
				__( 'Search results: %s', 'wp-irving' ),
				$wp_query->get( 's' )
			);
			$this->set_title( $title );
		} else {
			// If queried object is a valid article post type.
			$queried_object = $wp_query->get_queried_object() ?? $wp_query->post;
			if ( $queried_object instanceof \WP_Post ) {
				$post_id = $queried_object->ID;

				$this->apply_meta( $post_id );
				$this->apply_social_meta( $post_id );
				$this->set_title( $this->get_meta_title( $post_id ) );
			} elseif ( $queried_object instanceof \WP_Term ) {
				$this->set_title( $queried_object->name );
			}
		}

		// Allow for further customizations.
		do_action( 'wp_irving_head', $this, $wp_query );

		return $this;
	}

	/**
	 * Set a title tag as a child of the Head component.
	 *
	 * @param string $value The title value.
	 * @return Head
	 */
	public function set_title( $value ) {

		// Decode HTML title.
		$value = html_entity_decode( $value );

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
				'content'  => html_entity_decode( $content ),
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
	 * Helper function for add a new script tag.
	 *
	 * @param string $src Script tag src url.
	 * @param bool   $defer If script should defer loading until DOMContentLoaded.
	 * @param bool   $async If script should load asynchronous.
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
	public function add_tag( $tag, $attributes = [] ) {

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

	/**
	 * Apply basic meta tags.
	 *
	 * @param  integer $post_id Post ID.
	 */
	public function apply_meta( $post_id ) {

		// Meta description.
		$meta_description = $this->get_meta_description( $post_id );
		if ( ! empty( $meta_description ) ) {
			$this->add_tag(
				'meta',
				[
					'name'    => 'description',
					'content' => esc_attr( $meta_description ),
				]
			);
		}

		// Meta keywords.
		$meta_keywords = (string) get_post_meta( $post_id, '_meta_keywords', true );
		if ( ! empty( $meta_keywords ) ) {
			$this->add_tag(
				'meta',
				[
					'name'    => 'keywords',
					'content' => esc_attr( $meta_keywords ),
				]
			);
		}
	}

	/**
	 * Apply social meta tags.
	 *
	 * @param  integer $post_id Post ID.
	 */
	public function apply_social_meta( $post_id ) {

		// Open graph meta.
		$this->add_meta( 'og:url', get_the_permalink( $post_id ) );
		$this->add_meta( 'og:type', 'article' );
		$this->add_meta( 'og:title', $this->get_social_title( $post_id ) );
		$this->add_meta( 'og:description', $this->get_social_description( $post_id ) );

		// Optional meta.
		$image_url = $this->get_social_image_url( $post_id, [ 'resize' => '1200,630' ] );
		if ( ! empty( $image_url ) ) {
			$this->add_meta( 'og:image', $image_url );
		}

		// Twitter specific meta.
		$twitter_meta = [
			'twitter:card'        => 'summary_large_image',
			'twitter:title'       => $this->get_social_title( $post_id ),
			'twitter:description' => $this->get_social_description( $post_id ),
			'twitter:image'       => $image_url,
		];

		// Add Twitter tags.
		foreach ( $twitter_meta as $name => $content ) {
			if ( empty( $content ) ) {
				return;
			}

			$this->add_tag(
				'meta',
				[
					'name'    => $name,
					'content' => $content,
				]
			);
		}
	}

	/**
	 * Get the meta title for a post.
	 *
	 * @param  integer $post_id Post ID.
	 * @return string Meta title.
	 */
	public function get_meta_title( $post_id ) : string {

		// Return WP-SEO title.
		$meta_title = (string) get_post_meta( $post_id, '_meta_title', true );
		if ( ! empty( $meta_title ) ) {
			return $meta_title;
		}

		return (string) get_the_title( $post_id );
	}

	/**
	 * Get the social title for a post.
	 *
	 * @param  integer $post_id Post ID.
	 * @return string Social title.
	 */
	public function get_social_title( $post_id ) : string {

		// Use social title, or fallback to meta title.
		$social_title = get_post_meta( $post_id, 'social_title', true );
		if ( ! empty( $social_title ) ) {
			return $social_title;
		}

		return (string) $this->get_meta_title( $post_id );
	}

	/**
	 * Get the meta descrption for a post.
	 *
	 * @param  integer $post_id Post ID.
	 * @return string Meta description.
	 */
	public function get_meta_description( $post_id ) : string {

		// Return WP-SEO description.
		$meta_description = (string) get_post_meta( $post_id, '_meta_description', true );
		if ( ! empty( $meta_description ) ) {
			return $meta_description;
		}

		// Modify global state.
		global $post;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$backup_post = $post;

		// Setup post data for this item.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
		$post = get_post( $post_id );
		setup_postdata( $post );
		$excerpt = get_the_excerpt();

		// Undo global modification.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
		$post = $backup_post;
		setup_postdata( $post );

		return $excerpt;
	}

	/**
	 * Get the social description for a post.
	 *
	 * @param  integer $post_id Post ID.
	 * @return string Social description.
	 */
	public function get_social_description( $post_id ) : string {

		// Use social description, or fallback to meta description.
		$social_description = get_post_meta( $post_id, 'social_description', true );
		if ( ! empty( $social_description ) ) {
			return $social_description;
		}

		return (string) $this->get_meta_description( $post_id );
	}

	/**
	 * Get the social image for a post.
	 *
	 * @param  integer $post_id     Post ID.
	 * @param  array   $photon_args Photon API arguments.
	 * @return string Social image url.
	 */
	public function get_social_image_url( $post_id, $photon_args = [] ) {

		// Get image url.
		$image_id  = absint( get_post_meta( $post_id, 'social_image_id', true ) );
		$image_url = wp_get_attachment_image_url( $image_id, 'full' );

		// Fallback to featured image.
		if ( empty( $image_url ) ) {
			$image     = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
			$image_url = $image[0] ?? '';
		}

		if ( empty( $image_url ) ) {
			return '';
		}

		// Remove existing photon arg if present.
		$image_url = remove_query_arg( [ 'fit' ], $image_url );

		return add_query_arg(
			$photon_args,
			$image_url
		);
	}
}
