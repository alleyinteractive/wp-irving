<?php
/**
 * Class file for the Social Links component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Social Links component.
 */
class Social_Links extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'social-links';

	/**
	 * Label for custom fields.
	 *
	 * @var string
	 */
	public $label = 'Social Links';

	/**
	 * Label for custom fields.
	 *
	 * @var string
	 */
	public static $services = [];

	/**
	 * Component constructor.
	 *
	 * @param string $name     Unique component slug or array of name, config,
	 *                         and children value.
	 * @param array  $config   Component config.
	 * @param array  $children Component children.
	 */
	public function __construct( $name = '', array $config = [], array $children = [] ) {
		parent::__construct( $name, $config, $children );

		// Set up default services
		self::$services = [
			'facebook'  => __( 'Facebook', 'wp-irving' ),
			'twitter'   => __( 'Twitter', 'wp-irving' ),
			'linkedin'  => __( 'LinkedIn', 'wp-irving' ),
			'pinterest' => __( 'Pinterest', 'wp-irving' ),
			'whatsapp'  => __( 'WhatsApp', 'wp-irving' ),
		];
	}

	/**
	 * Helper for adding social link services.
	 *
	 * @param mixed  $slug A slug for the service, usually corresponds to a custom field name.
	 * @param string $label A text label for the services, usually used as a label for a custom field.
	 */
	public static function add_services( $slug, $label = '' ) {
		// Allow an array of services to be merged with defaults
		if ( is_array( $slug ) ) {
			self::$services = array_merge( self::$services, $slug );
		} else {
			self::$services[ $slug ] = $label;
		}
	}

	/**
	 * Helper for removing social link services.
	 *
	 * @param mixed $slug A slug for the service, usually corresponds to a custom field name.
	 * @return Social_Links Instance of this class.
	 */
	public static function remove_services( $slug ) {
		// Allow an array of services to be unset
		if ( is_array( $slug ) ) {
			foreach ( $slug as $service ) {
				unset( self::$services[ $service ] );
			}
		} else {
			unset( self::$services[ $slug ] );
		}
	}

	/**
	 * Define a default config shape.
	 *
	 * @return array Default config.
	 */
	public function default_config() {
		return [
			'display_icons'     => true,
		];
	}

	/**
	 * Parse data from an array of links.
	 *
	 * @param array $links Array of config arrays for creating child Social_Links_Item components.
	 * @return Social_Links Instance of this class.
	 */
	public function add_links( $links ) : self {
		if ( ! empty( $links ) ) {
			// Hydrate the items.
			foreach ( $links['links'] as $link ) {
				$link['display_icon'] = $this->get_config( 'display_icons' );
				$this->add_link_from_array( $link );
			}
		}

		return $this;
	}

	/**
	 * Create child link component from a config array.
	 *
	 * @param array $config Configuration array for a Social_Links_Item component
	 * @return Social_Links Instance of this class.
	 */
	public function add_link( $config ) : self {
		$type = $config['type'] ?? '';
		$service_types = array_keys( $this->get_config( 'services' ) );

		// Don't add if it's not a configured service
		if ( ! empty( $config ) && in_array( $type, $service_types ) ) {
			$this->set_children( [
				new Social_Links_Item( '', [
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
