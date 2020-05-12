<?php
/**
 * Class Cache_Tests
 *
 * @package WP_Irving
 */

/**
 * Tests for integration with WPCOM Legacy Redirector.
 */
class Cache_Tests extends WP_UnitTestCase {

	/**
	 * Helpers class instance.
	 *
	 * \WP_Irving_Test_Helpers
	 */
	static $helpers;

	/**
	 * Helpers class instance.
	 *
	 * \WP_Irving\Cache
	 */
	static $cache;

	/**
	 * Components endpoint instance.
	 *
	 * \WP_Irving\REST_API\Components_Endpoint
	 */
	static $components_endpoint;

	/**
	 * Test suite setup.
	 */
	public static function setUpBeforeClass() {
		self::$helpers = new \WP_Irving\Test_Helpers();
		self::$cache   = \WP_Irving\Cache::instance();
	}

	/**
	 * Test post purge urls.
	 */
	public function test_get_post_purge_urls() {
		// insert a post.
		$current_post = $this->factory->post->create_and_get(
			array(
				'post_title' => rand_str(),
				'post_date'  => '2020-01-01 00:00:00',
			)
		);
	}
}
