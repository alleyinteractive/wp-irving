<?php
/**
 * Class file for the Social Share component.
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
	 * Define a default config shape.
	 *
	 * @return array Default config.
	 */
	public function default_config() {
		return [
			'post_id'        => 0,
			'display_icons'  => true,
		];
	}

	/**
	 * Array of services.
	 *
	 * @var string
	 */
	public static $services = [
		'facebook'  => [
			'share_url' => 'https://www.facebook.com/sharer.php/',
			'params'    => [
				'u' => 'get_url',
			],
		],
		'twitter'   => [
			'share_url' => 'https://twitter.com/share/',
			'params'    => [
				'text' => 'get_title',
				'url'  => 'get_url',
			]
		],
		'whatsapp'  => [
			'share_url' => 'https://api.whatsapp.com/send/',
			'params'    => [
				'text' => 'get_cta',
			],
		],
		'linkedin'  => [
			'share_url' => 'https://www.linkedin.com/shareArticle/',
			'params'    => [
				'url'     => 'get_url',
				'title'   => 'get_title',
				'summary' => 'get_excerpt',
			],
		],
		'pinterest' => [
			'share_url' => 'https://pinterest.com/pin/create/button/',
			'params'    => [
				'url'         => 'get_url',
				'media'       => 'get_featured_image_url',
				'description' => 'get_excerpt',
			],
		],
	];

	/**
	 * Add Social_Item component for each sharing service.
	 *
	 * @param WP_Post $post Post object these sharing buttons will share.
	 * @return Social_Share Return this class.
	 */
	public function add_links_for_post( $post ) : self {
		foreach ( self::$services as $service => $config ) {
			$url = $config['share_url'];

			foreach( $config['params'] as $param => $value_function ) {
				$url = add_query_arg(
					$param,
					call_user_func_array( [ $this, $value_function ], [ $post ] ),
					$url
				);
			}

			$this->add_link( [
				'type' => $service,
				'url'   => $url,
				'display_icon' => $this->get_config( 'display_icons' ),
			] );
		}

		return $this;
	}

	/**
	 * Retrieve a shareable url.
	 *
	 * @param WP_Post $post Post object to retrieve url for.
	 */
	public function get_url( $post ) {
		return urlencode( get_permalink( $post ) );
	}

	/**
	 * Retrieve a shareable title.
	 *
	 * @param WP_Post $post Post object to retrieve title for.
	 */
	public function get_title( $post ) {
		return urlencode( get_the_title( $post ) );
	}

	/**
	 * Retrieve a shareable excerpt.
	 *
	 * @param WP_Post $post Post object to retrieve excerpt for.
	 */
	public function get_excerpt( $post ) {
		return urlencode( $post->post_excerpt );
	}

	/**
	 * Retrieve shareable call to action text.
	 *
	 * @param WP_Post $post Post object to retrieve call to action text for.
	 */
	public function get_cta( $post ) {
		return urlencode( sprintf(
			'Check out this story: %1s from Thrive global %2s',
			$this->get_title( $post ),
			$this->get_url( $post )
		) );
	}

	/**
	 * Retrieve a shareable feature image url.
	 *
	 * @param WP_Post $post Post object to retrieve feature image url for.
	 */
	public function get_featured_image_url( $post ) {
		$attachment_id = get_post_thumbnail_id( $post->ID );

		if ( ! empty( $thmbnail_id ) ) {
			return urlencode( wp_get_attachment_url( $attachment_id ) );
		}

		return '';
	}
}
