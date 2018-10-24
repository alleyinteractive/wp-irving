<?php
/**
 * Trait for use by social links and sharing.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

/**
 * Defines the Social Links component.
 */
trait Social {

	/**
	 * Helper for adding social link services.
	 *
	 * @param mixed $service A slug for the service or an array of services.
	 * @param array $config Configuration array for provided service.
	 */
	public static function add_service( $service, $config = [] ) {
		// Allow an array of services to be merged with defaults.
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
	 */
	public static function remove_service( $service ) {
		// Allow an array of services to be unset.
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
	 * @param array $config Configuration array for a Social_Item component.
	 * @return Social Instance of this class.
	 */
	public function add_link( $config ) : self {
		$type = $config['type'] ?? '';
		$service_types = array_keys( self::$services );

		// Don't add if it's not a configured service.
		if ( ! empty( $config ) && in_array( $type, $service_types ) ) {
			$this->set_children( [
				new Component\Social_Item( '', [
					'type'         => $config['type'] ?? '',
					'url'          => $config['url'] ?? '',
					'display_icon' => $config['display_icon'] ?? '',
				] ),
			], true );
		}

		return $this;
	}
}
