<?php
/**
 * Class file for the Disqus component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Disqus component.
 */
class Disqus extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'disqus';

	/**
	 * Define the default config for Disqus
	 *
	 * @return array Default config values for this component.
	 */
	public function default_config() {
		return [
			'page_url'        => '',
			'page_identifier' => '',
			'forum_shortname' => $this->get_forum_shortname(),
		];
	}

	/**
	 * Get Forum shortname for Disqus embeds
	 *
	 * @return array Filtered value for disqus forum shortname
	 */
	public function get_forum_shortname() {
		return apply_filters( 'wp_irving_disqus_forum_shortname', get_option( 'disqus_forum_shortname' ) );
	}

	/**
	 * Configure this component based on a provided post
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function set_config_from_post( \WP_Post $post ) {
		$this->set_config( 'page_url', get_the_permalink( $post ) );
		$this->set_config( 'page_identifier', $post->ID . ' ' . $post->guid );

		return $this;
	}
}
