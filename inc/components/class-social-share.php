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
class Social_Share extends Component {

	use \WP_Irving\Social;

	/**
	 * Unique component slug.
	 *
	 * @var string
	 */
	public $name = 'social-share';

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

		self::add_services( [
			'facebook'  => [
				'share_url' => 'https://www.facebook.com/sharer.php/',
			],
			'twitter'   => [
				'share_url' => 'https://twitter.com/share/',
			],
			'whatsapp'  => [
				'share_url' => 'https://api.whatsapp.com/send/',
			],
			'linkedin'  => [
				'share_url' => 'https://www.linkedin.com/shareArticle/',
			],
			'pinterest' => [
				'share_url' => 'https://pinterest.com/pin/create/button/',
			],
		] );
	}
}
