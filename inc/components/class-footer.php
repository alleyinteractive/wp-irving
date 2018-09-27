<?php
/**
 * Class file for Irving's Footer component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Component;

/**
 * Defines the Footer component.
 */
class Footer extends Component {

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'footer';

	/**
	 * Define the default config of a footer.
	 *
	 * @return Footer An instance of this component.
	 */
	public function default_config() {
		return [
			'copyright' => sprintf(
				/* translators: %d: current year */
				esc_html__( 'Copyright &copy;%d Alley Interactive', 'wp-irving' ),
				date( 'Y' )
			),
		];
	}
}
