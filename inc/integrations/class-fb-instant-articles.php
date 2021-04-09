<?php
/**
 * WP Irving integration for FB Instant Articles.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;
use WP_Irving\Components;

/**
 * Facebook Instant Articles.
 */
class FB_Instant_Articles {
	use Singleton;

	/**
	 * Constructor for class.
	 */
	public function setup() {

		// Ensure the plugin is activated.
		if (
			! is_plugin_active( 'fb-instant-articles/facebook-instant-articles.php' )
			|| ! defined( 'IA_PLUGIN_VERSION' )
		) {
			return;
		}

		if ( ! is_admin() ) {
			add_filter( 'wp_irving_component_children', [ $this, 'inject_fb_instant_articles_tags_into_head_children' ], 10, 3 );
		}
	}

	/**
	 * Parse the head markup output by FB Instant Articles, injecting it into
	 * the Head component.
	 *
	 * @param array  $children Children for this component.
	 * @param array  $config   Config for this component.
	 * @param string $name     Name of this component.
	 * @return array
	 */
	public function inject_fb_instant_articles_tags_into_head_children( array $children, array $config, string $name ): array {

		if ( ! function_exists( 'inject_url_claiming_meta_tag' ) ) {
			return $children;
		}

		// Ony run this action on the `irving/head` in a `site` context.
		if (
			'irving/head' !== $name
			|| 'page' !== ( $config['context'] ?? 'page' )
		) {
			return $children;
		}

		return array_merge(
			$children,
			Components\html_to_components( $this->get_fb_instant_articles_url_claiming_meta_tag_markup(), [ 'meta' ] )
		);
	}

	/**
	 * Capture the markup output for the `fb:pages` tag.
	 *
	 * @return string
	 */
	public function get_fb_instant_articles_url_claiming_meta_tag_markup(): string {
		ob_start();
		\inject_url_claiming_meta_tag();
		return ob_get_clean();
	}
}
