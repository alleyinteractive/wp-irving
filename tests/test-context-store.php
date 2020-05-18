<?php
/**
 * Class Test_Context_Store
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_UnitTestCase;

/**
 * Tests for the WP_Irving\Context_Store class.
 *
 * @group context
 */
class Test_Context_Store extends WP_UnitTestCase {

	/**
	 *
	 * @var WP_Irving\Context_Store
	 */
	public static $context;

	/**
	 * Set up shared fixtures.
	 */
	public static function setUpBeforeClass() {
		self::$context = new Context_Store();
	}

	/**
	 * Test helper for getting the shared Context_Store class.
	 *
	 * @return WP_Irving\Context_Store
	 */
	public function get_context() {
		return self::$context;
	}

	/**
	 * Data provider for testing different data types.
	 *
	 * @return void
	 */
	public function get_context_values() {
		return [
			[ 'Test Value', 'string' ],
			[ 10, 'int' ],
			[ [ 1, 2, 3 ], 'array' ],
		];
	}

	/**
	 * Test context setting and getting.
	 *
	 * @dataProvider get_context_values
	 *
	 * @param mixed  $value The value to set.
	 * @param string $type  The type of variable to test.
	 */
	public function test_context_set_and_get( $value, $type ) {
		$this->get_context()->set( [ 'test/context' => $value ] );
		$this->assertEquals( $value, $this->get_context()->get( 'test/context' ), "Could not confirm context was updated for type ${type}" );

		// Clean up.
		$this->get_context()->reset( 'test/context' );
	}

	/**
	 * Test the reset method.
	 */
	public function test_context_reset() {
		$values = [ 1, 2, 3 ];

		foreach ( $values as $value ) {
			$this->get_context()->set( [ 'test/context' => $value ] );
		}

		// Remove the last step before resetting.
		array_pop( $values );

		// Reverse through the array.
		while ( ! empty( $values ) ) {
			$value = array_pop( $values );
			$this->get_context()->reset();
			$this->assertEquals( $value, $this->get_context()->get( 'test/context' ), 'Could not reset context.' );
		}
	}
}
