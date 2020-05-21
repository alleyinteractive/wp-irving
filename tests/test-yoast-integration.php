<?php
/**
 * Class Test_Yoast_Integration.
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_UnitTestCase;

/**
 * Tests for the WP_Irving\Yoast integration.
 *
 * @group integration
 */
class Test_Yoast_Integration extends WP_UnitTestCase {

	/**
	 * Helpers class instance.
	 *
	 * \WP_Irving_Test_Helpers
	 */
	static $helpers;

	/**
	 * Holding the Yoast object.
	 *
	 * @var WP_Irving\Yoast
	 */
	public static $object = null;

	/**
	 * Test suite setup.
	 */
	public static function setUpBeforeClass() {
		self::$helpers = new Test_Helpers();
		self::$object  = new Yoast();;
	}

	/**
	 * Get the class instance.
	 *
	 * @return WP_Irving\Yoast
	 */
	public function get_yoast() {
		return self::$object;
	}

	public function setup_globals() {
		echo 'test';
	}

	/**
	 * Test the `inject_head_tags()` method.
	 *
	 * @todo What does the global context need to be for this to work?
	 */
	public function test_inject_head_tags() {

		$this->setup_globals();

		// Create a new helmet instance.
		$helmet = new Component( 'irving/helmet' );

		// Test that the Yoast tags were injected properly.
		$this->assertEquals(
			new Component( 'irving/helmet' ),
			$this->get_yoast()->inject_head_tags( $helmet )
		);
	}

	/**
	 * Test the `parse_head_markup()` method.
	 *
	 * @todo What does the global context need to be for this to work?
	 */
	public function test_parse_head_markup(){
		$markup = $this->get_yoast()::parse_head_markup();

	}
}
