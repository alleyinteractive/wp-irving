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
			add_filter(
				'wp_components_head_meta_title',
				[ $this, 'get_yoast_title' ]
			);

			add_filter(
				'wp_components_head_social_title',
				[ $this, 'get_yoast_social_title' ]
			);

			add_filter(
				'wp_components_head_meta_description',
				[ $this, 'get_yoast_meta_description' ]
			);

			add_filter(
				'wp_components_head_social_description',
				[ $this, 'get_yoast_social_description' ]
			);

			add_filter(
				'wp_components_head_image_id',
				[ $this, 'get_yoast_social_image_id' ]
			);

			add_filter(
				'wp_components_head_deindex_url',
				[ $this, 'get_yoast_is_deindexed' ]
			);
		}
	}

	/**
	 * Filter to return the title.
	 *
	 * @param string $title Already existing title.
	 * @return string Updated title value.
	 */
	public function get_yoast_title( string $title ) : string {
		// Workaround for is_singular() not being set.
		global $wp_query;
		$wp_query->is_singular = true;

		return \WPSEO_Frontend::get_instance()->title( $title );
	}

	/**
	 * Filter to return the social title.
	 *
	 * @param string $social_title Already existing social title.
	 * @return string Updated social title value.
	 */
	public function get_yoast_social_title( string $social_title ) : string {
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
	 * Filter to return the social description.
	 *
	 * @param string $social_description Already existing social description.
	 * @return string Updated social description value.
	 */
	public function get_yoast_social_description( string $social_description ) : string {
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
	 * Filter to return the social image ID.
	 *
	 * @param int $social_image Already existing social image ID.
	 * @return int Updated social image ID.
	 */
	public function get_yoast_social_image_id( int $social_image ) : int {
		return \WPSEO_Meta::get_value( 'opengraph-image-id', get_the_id() );
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

		$deindex = \WPSEO_Frontend::get_instance()->get_robots();
		return strpos( $deindex, 'noindex' ) !== false;
	}
}

add_action(
	'init',
	function() {
		new \WP_Irving\Yoast();
	}
);
