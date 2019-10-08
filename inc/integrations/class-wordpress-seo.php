<?php
/**
 * WP Irving integration for WordPress_SEO.
 *
 * @package WP_Irving;
 */

namespace WP_Irving;

/**
 * Class to add WordPress_SEO's query modifications.
 */
class WordPress_SEO {

    /**
	 * Yoast option meta key.
	 *
	 * @var string
	 */
	private $meta_key = 'wpseo';

	/**
	 * Constructor for class.
	 */
	public function __construct() {
		// Ensure Yoast plugin exists and is enabled.
		if ( ! class_exists( '\WPSEO_Options' ) ) {
			return;
        }
        
		add_filter( 'wp_components_head_meta_webmaster_tools_codes', [ $this, 'get_webmaster_tools_codes' ] );
    }
    
    /**
	 * Adding WordPress engines codes.
	 *
	 * @param array $codes Array of engine codes.
	 */
	public function get_webmaster_tools_codes( array $data ) : array {
		$yoast_meta = get_option( $this->meta_key );

		if ( empty( $yoast_meta ) ) {
			return $data;
		}

        foreach ( $yoast_meta as $name => $meta ) {
			switch( $name ) {
				case 'msverify':
					$data['msvalidate.01'] = $meta ?? '';
					break;
				case 'baiduverify':
					$data['baidu-site-verification'] = $meta ?? '';
					break;
				case 'googleverify':
					$data['google-site-verification'] = $meta ?? '';
					break;
				case 'yandexverify':
					$data['yandex-verification'] = $meta ?? '';
					break;
				default:
					break;
			}
		}

		return $data;
	}
}

add_action( 'init', function() {
	new \WP_Irving\WordPress_SEO();
} );

