<?php
/**
 * Class file for Irving's Byline component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Byline component.
 */
class Byline extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'byline';

	/**
	 * Define the default config of a byline.
	 *
	 * @return array Default config values for this component.
	 */
	public function default_config() {
		return [
			'avatar'      => null,
			'displayName' => '',
			'id'          => 0,
			'link'        => '',
			'slug'        => '',
		];
	}

	/**
	 * Create this component form a CAP guest author object.
	 *
	 * @param Object $coauthor CAP guest author object.
	 * @return Byline An instance of this object.
	 */
	public function set_coauthor( $coauthor ) {

		// Validate object.
		if ( 'guest-author' !== ( $coauthor->type ?? '' ) ) {
			return null;
		}

		// Set byline configs.
		$this->set_config( 'id', $coauthor->ID );
		$this->set_config( 'displayName', $coauthor->display_name );
		$this->set_config( 'slug', $coauthor->user_login );
		$this->set_config( 'link', get_author_posts_url( $coauthor->ID, $coauthor->user_nicename ) );

		// Set the avatar image.
		$avatar_id = get_post_thumbnail_id( $coauthor->ID );
		if ( 0 !== absint( $avatar_id ) ) {
			$this->set_config(
				'avatar',
				( new \WP_Irving\Component\Image() )
					->set_attachment_id( $avatar_id )
			);
		}

		return $this;
	}
}
