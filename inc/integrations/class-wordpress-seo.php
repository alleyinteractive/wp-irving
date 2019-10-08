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
        
		add_filter( 'wp_irving_components_route', [ $this, 'get_webmaster_tools_codes' ], 10, 1 );
    }
    
    /**
	 * Find any matching redirect for requested path and include in response data.
	 *
	 * @param array             $data     WP Irving response data.
	 * @param \WP_REST_Response $request  REST request.
	 */
	public function get_webmaster_tools_codes( $data ) : array {
		$yoast_meta                = get_option( $this->meta_key );
		$data['verificationCodes'] = [];
        $engines                   = [
            'bing'   => 'msverify',
            'baidu'  => 'baiduverify',
            'google' => 'googleverify',
            'yandex' => 'yandexverify',
        ];

        foreach ( $engines as $name => $meta ) {
			$data['verificationCodes'][ $name ] = $yoast_meta[ $meta ] ?? '';
        }

		return $data;
	}
}

add_action( 'init', function() {
	new \WP_Irving\WordPress_SEO();
} );

