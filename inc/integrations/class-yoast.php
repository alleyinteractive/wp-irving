<?php
/**
 * WP Irving integration for Yoast SEO.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Integrations;

use WP_Irving\Components;
use WP_Irving\Component;

/**
 * Yoast.
 */
class Yoast {

	/**
	 * Class instance.
	 *
	 * @var null|self
	 */
	protected static $instance;

	/**
	 * Get class instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Constructor for class.
	 */
	public function setup() {

		// Ensure Yoast exists and is enabled.
		if ( ! class_exists( '\WPSEO_Frontend' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			// Parse Yoast's head markup and inject it into the Head component.
			add_filter( 'wp_irving_component_children', [ $this, 'inject_yoast_tags_into_head_children' ], 10, 3 );
			add_filter( 'wp_irving_integrations_config', [ $this, 'inject_yoast_schema_into_integrations_config' ], 10, 1 );
		}
	}

	/**
	 * Parse Yoast's head markup and inject the `application/ld+json` schema into
	 * the integrations config to be automatically managed on the front-end.
	 *
	 * @param array $config The current configuration.
	 * @return array The updated configuration.
	 */
	public function inject_yoast_schema_into_integrations_config( array $config ): array {

		$components = Components\html_to_components( $this->get_yoasts_head_markup(), [ 'script' ] );
		$schema     = '';

		foreach ( $components as $component ) {
			$component_config   = $component->get_config();
			$component_children = $component->get_children();

			if (
				isset( $component_config['type'] )
				&& 'application/ld+json' === $component_config['type']
			) {
				$schema = $component->get_children()[0];
			}
		}

		// Bail early if no schema has been set.
		if ( empty( $schema ) ) {
			return $config;
		}

		return array_merge(
			$config,
			[ 'yoast_schema' => [ 'content' => $schema ] ]
		);
	}

	/**
	 * Parse Yoast's head markup, inject into the Head component.
	 *
	 * @param array  $children Children for this component.
	 * @param array  $config   Config for this component.
	 * @param string $name     Name of this component.
	 * @return array
	 */
	public function inject_yoast_tags_into_head_children( array $children, array $config, string $name ): array {

		// Ony run this action on the `irving/head` in a `page` context.
		if (
			'irving/head' !== $name
			|| 'page' !== ( $config['context'] ?? 'page' )
		) {
			return $children;
		}

		return array_merge(
			$children,
			Components\html_to_components( $this->get_yoasts_head_markup(), [ 'title', 'meta', 'link' ] )
		);
	}

	/**
	 * Capture the markup output by Yoast in the site <head>.
	 *
	 * @return string
	 */
	public function get_yoasts_head_markup(): string {
		ob_start();
		\do_action( 'wpseo_head' ); // phpcs:ignore
		return ob_get_clean();
	}
}
