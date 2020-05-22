<?php
/**
 * WP Irving integration for Yoast SEO.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Yoast.
 */
class Yoast {

	/**
	 * Constructor for class.
	 */
	public function __construct() {

		// Ensure Yoast exists and is enabled.
		if ( ! class_exists( '\WPSEO_Frontend' ) ) {
			return;
		}

		if ( ! is_admin() ) {

			// Parse Yoast's head markup and inject it into the Helmet component.
			add_filter( 'wp_irving_page_helmet_component', [ $this, 'inject_yoast_tags' ] );

			// Integrate Yoast with the WP Components plugin.
			$this->add_wp_component_filters();
		}
	}

	/**
	 * Parse Yoast's head markup, inject into the Helmet component.
	 *
	 * @param Component $helmet Helmet component to modify.
	 * @return Component
	 */
	public function inject_yoast_tags( Component $helmet ): Component {
		return Templates\inject_head_tags(
			$helmet,
			Templates\parse_html( $this->get_yoasts_head_markup(), [ 'title', 'meta', 'link' ] )
		);
	}

	/**
	 * Capture the markup output by Yoast in the site <head>.
	 *
	 * @return string
	 */
	public function get_yoasts_head_markup(): string {
		ob_start();
		\do_action( 'wpseo_head' );
		return ob_get_clean();
	}

	/**
	 * Setup Yoast related filters for integration with WP Components.
	 */
	public function add_wp_component_filters() {

		// Remove default trailing title in favor of Yoast.
		add_filter( 'wp_components_head_append_trailing_title', '__return_false' );

		add_filter(
			'wp_components_head_title',
			[ $this, 'get_yoast_title' ]
		);

		add_filter(
			'wp_components_head_meta_title',
			[ $this, 'get_yoast_title' ]
		);

		add_filter(
			'wp_components_head_og_title',
			[ $this, 'get_yoast_og_title' ]
		);

		add_filter(
			'wp_components_head_twitter_title',
			[ $this, 'get_yoast_twitter_title' ]
		);

		add_filter(
			'wp_components_head_meta_description',
			[ $this, 'get_yoast_meta_description' ]
		);

		add_filter(
			'wp_components_head_og_description',
			[ $this, 'get_yoast_og_description' ]
		);

		add_filter(
			'wp_components_head_twitter_description',
			[ $this, 'get_yoast_twitter_description' ]
		);

		add_filter(
			'wp_components_head_image_id',
			[ $this, 'get_yoast_social_image_id' ]
		);

		add_filter(
			'wp_components_head_deindex_url',
			[ $this, 'get_yoast_is_deindexed' ]
		);

		add_filter(
			'wp_components_head_additional_meta_tags',
			[ $this, 'get_yoast_webmaster_tools_tags' ]
		);
	}

	/**
	 * Filter to return the title.
	 *
	 * @param string $title Already existing title.
	 * @return string Updated title value.
	 */
	public function get_yoast_title( string $title ) : string {
		return \WPSEO_Frontend::get_instance()->title( $title );
	}

	/**
	 * Filter to return the Open Graph title.
	 *
	 * @param string $social_title Already existing title.
	 * @return string Updated title value.
	 */
	public function get_yoast_og_title( string $social_title ) : string {
		global $wp_query;
		global $wpseo_og;

		// Workaround for is_singular() not being set.
		$wp_query->is_singular = true;

		// Use the global if available.
		if ( empty( $wpseo_og ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			$wpseo_og = new \WPSEO_OpenGraph();
		}

		return $wpseo_og->og_title( false );
	}

	/**
	 * Filter to return the Twitter title.
	 *
	 * @param string $social_title Already existing title.
	 * @return string Updated title value.
	 */
	public function get_yoast_twitter_title( string $social_title ) : string {
		$twitter_title = \WPSEO_Meta::get_value( 'twitter-title', get_the_id() );

		if ( empty( $twitter_title ) ) {
			$twitter_title = \WPSEO_Frontend::get_instance()->title( '' );
		}

		return $twitter_title;
	}

	/**
	 * Filter to return the meta description.
	 *
	 * @param string $description Already existing meta description.
	 * @return string Updated meta description value.
	 */
	public function get_yoast_meta_description( string $description ) : string {
		// Workaround for is_singular() not being set.
		global $wp_query;
		$wp_query->is_singular = true;

		return \WPSEO_Frontend::get_instance()->metadesc( false );
	}

	/**
	 * Filter to return the Open Graph description.
	 *
	 * @param string $social_description Already existing description.
	 * @return string Updated description value.
	 */
	public function get_yoast_og_description( string $social_description ) : string {
		// Workaround for is_singular() not being set.
		global $wp_query;
		$wp_query->is_singular = true;

		global $wpseo_og;

		// Use the global if available.
		if ( empty( $wpseo_og ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			$wpseo_og = new \WPSEO_OpenGraph();
		}

		return $wpseo_og->description( false );
	}

	/**
	 * Filter to return the Twitter description.
	 *
	 * @param string $social_description Already existing description.
	 * @return string Updated description value.
	 */
	public function get_yoast_twitter_description( string $social_description ) : string {
		// Workaround for is_singular() not being set.
		global $wp_query;
		$wp_query->is_singular = true;

		$twitter_description = \WPSEO_Meta::get_value( 'twitter-description', get_the_id() );

		if ( empty( $twitter_description ) ) {
			$twitter_description = \WPSEO_Frontend::get_instance()->metadesc( false );
		}

		return (string) $twitter_description;
	}

	/**
	 * Filter to return the social image ID.
	 *
	 * @param int $social_image Already existing social image ID.
	 * @return int Updated social image ID.
	 */
	public function get_yoast_social_image_id( int $social_image ) : int {
		return (int) \WPSEO_Meta::get_value( 'opengraph-image-id', get_the_id() );
	}

	/**
	 * Filter to return if the post should be deindexed.
	 *
	 * @param bool $deindex True if post is already set to be deindexed.
	 * @return bool True if post should be deindexed.
	 */
	public function get_yoast_is_deindexed( bool $deindex ) {
		// Workaround for is_singular() not being set.
		global $wp_query;
		$wp_query->is_singular = true;

		// Only if the method exists.
		if ( ! method_exists( '\WPSEO_Frontend', 'get_robots' ) ) {
			return '';
		}

		$deindex = \WPSEO_Frontend::get_instance()->get_robots();
		return strpos( $deindex, 'noindex' ) !== false;
	}

	/**
	 * Filter to return the webmaster tools tags.
	 *
	 * @param array $tags Meta tags.
	 * @return array
	 */
	public function get_yoast_webmaster_tools_tags( array $tags ) : array {
		$new_tags = [
			'baidu-site-verification'  => \WPSEO_Options::get( 'baiduverify', '' ),
			'msvalidate.01'            => \WPSEO_Options::get( 'msverify', '' ),
			'google-site-verification' => \WPSEO_Options::get( 'googleverify', '' ),
			'yandex-verification'      => \WPSEO_Options::get( 'yandexverify', '' ),
			'p:domain_verify'          => \WPSEO_Options::get( 'pinterestverify', '' ),
		];

		return array_merge( $tags, $new_tags );
	}
}

add_action(
	'init',
	function() {
		new \WP_Irving\Yoast();
	}
);
