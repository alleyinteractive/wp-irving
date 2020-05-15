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
		$current_user = $this->factory->user->create_and_get(
			[
				'user_login'  => 'alley',
			]
		);
		$current_post = $this->factory->post->create_and_get(
			[
				'post_title'  => rand_str(),
				'post_date'   => '2020-01-01 00:00:00',
				'post_author' => $current_user->ID,
			]
		);
		$current_term = $this->factory->term->create_and_get(
			[
				'name'     => 'Test',
				'taxonomy' => 'post_tag',
			]
		);
		$this->factory->term->add_post_terms( $current_post->ID, [ $current_term->slug ], 'post_tag' );

		$this->assertEquals(
			self::$cache->get_post_purge_urls( $current_post->ID ),
			[
				'http://example.org/2020/01/01/' . $current_post->post_title . '/',
				'http://example.org/',
				'http://example.org/category/uncategorized' . '/',
				'http://example.org/category/uncategorized/feed/',
				'http://example.org/tag/' . $current_term->slug . '/',
				'http://example.org/tag/' . $current_term->slug . '/feed/',
				'http://example.org/author/' . $current_user->data->user_login . '/',
				'http://example.org/author/' . $current_user->data->user_login . '/feed/',
			]
		);
	}

	/**
	 * Test term purge urls.
	 */
	public function test_get_term_purge_urls() {
		$current_term = $this->factory->term->create_and_get(
			[
				'name'     => 'Test',
				'taxonomy' => 'category',
			]
		);

		$this->assertEquals(
			self::$cache->get_term_purge_urls( $current_term->term_id ),
			[
				'http://example.org/category/' . $current_term->slug . '/',
				'http://example.org/category/' . $current_term->slug . '/feed/',
			]
		);
	}

	/**
	 * Test user purge urls.
	 */
	public function test_get_user_purge_urls() {
		$current_user = $this->factory->user->create_and_get(
			[
				'user_login'  => 'alley',
			]
		);

		$this->assertEquals(
			self::$cache->get_user_purge_urls( $current_user ),
			[
				'http://example.org/author/' . $current_user->data->user_login . '/',
				'http://example.org/author/' . $current_user->data->user_login . '/feed/',
			]
		);
	}
}
