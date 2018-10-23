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
	 * Label for FM fields.
	 *
	 * @var string
	 */
	public $label = 'Social Links';

	/**
	 * Define a default config shape.
	 *
	 * @return array Default config.
	 */
	public function default_config() {
		return [
			'display_icons' => true,
			'location'     => 'social-links',
			'services'     => [
				'facebook'  => __( 'Facebook', 'wp-irving' ),
				'twitter'   => __( 'Twitter', 'wp-irving' ),
				'linkedin'  => __( 'LinkedIn', 'wp-irving' ),
				'pinterest' => __( 'Pinterest', 'wp-irving' ),
				'whatsapp'  => __( 'WhatsApp', 'wp-irving' ),
			],
		];
	}

	/**
	 * Component Fieldmanager fields.
	 *
	 * @return array Fieldmanager fields.
	 */
	public function get_fm_fields() {
		return [
			'links'        => new \Fieldmanager_Group( [
				'label'          => __( 'Social Link', 'wp-irving' ),
				'add_more_label' => __( 'Add Another Social Link', 'wp-irving' ),
				'limit'          => 0,
				'extra_elements' => 0,
				'sortable'       => true,
				'children'       => [
					'type' => new \Fieldmanager_Select( [
						'label'   => __( 'Social Platform', 'wp-irving' ),
						'options' => $this->get_config( 'services' ),
					] ),
					'url'  => new \Fieldmanager_Link( [
						'label' => __( 'URL', 'wp-irving' ),
					] ),
				],
			] ),
		];
	}

	/**
	 * Parse data from a saved FM field.
	 *
	 * @return Social_Links Instance of this class.
	 */
	public function parse_from_fm_data() : self {
		$links = get_option( 'social-links', [] );
		$display_icons = $this->get_config( 'display_icons' );

		if ( ! empty( $links['links'] ) ) {
			// Hydrate the items.
			foreach ( $links['links'] as $link ) {
				$this->children[] = ( new Social_Links_Item( '', [
					'type' => $link['type'] ?? '',
					'url' => $link['url'] ?? '',
					'display_icon' => $display_icons,
				] ) );
			}
		}

		return $this;
	}
}
