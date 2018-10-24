<?php
/**
 * Class file for the Social Links component.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Defines the Social Links component.
 */
trait Social {

	/**
	 * Array of services. This is static so developer only needs to add
	 * new services once for all social components to be affected.
	 *
	 * @var string
	 */
	public static $services = [];

	/**
	 * Helper for adding social link services.
	 *
	 * @param mixed  $service A slug for the service or an array of services.
	 * @param string $label A text label for the services, usually used as a label for a custom field.
	 */
	public static function add_services( $service, $config = [] ) {
		// Allow an array of services to be merged with defaults
		if ( is_array( $service ) ) {
			self::$services = array_merge( self::$services, $service );
		} else {
			self::$services[ $service ] = $config;
		}
	}

	/**
	 * Helper for removing social link services.
	 *
	 * @param mixed $service A slug for the service or an array of services.
	 * @return Social Instance of this class.
	 */
	public static function remove_services( $service ) {
		// Allow an array of services to be unset
		if ( is_array( $service ) ) {
			foreach ( $service as $slug ) {
				unset( self::$services[ $slug ] );
			}
		} else {
			unset( self::$services[ $service ] );
		}
	}

	/**
	 * Define a default config shape.
	 *
	 * @return array Default config.
	 */
	public function default_config() {
		return [
			'display_icons' => true,
		];
	}

	/**
	 * Parse data from an array of links.
	 *
	 * @param array $links Array of config arrays for creating child Social_Item components.
	 * @return Social Instance of this class.
	 */
	public function add_links( $links ) : self {
		if ( ! empty( $links ) ) {
			// Hydrate the items.
			foreach ( $links['links'] as $link ) {
				$link['display_icon'] = $this->get_config( 'display_icons' );
				$this->add_link( $link );
			}
		}

		return $this;
	}

	/**
	 * Create child link component from a config array.
	 *
	 * @param array $config Configuration array for a Social_Item component
	 * @return Social Instance of this class.
	 */
	public function add_link( $config ) : self {
		$type = $config['type'] ?? '';
		$service_types = array_keys( $this->get_config( 'services' ) );

		// Don't add if it's not a configured service
		if ( ! empty( $config ) && in_array( $type, $service_types ) ) {
			$this->set_children( [
				new Social_Item( '', [
					'type'         => $config['type'] ?? '',
					'url'          => $config['url'] ?? '',
					'display_icon' => $config['display_icon'] ?? '',
				] ),
				true
			] );
		}

		return $this;
	}
}
