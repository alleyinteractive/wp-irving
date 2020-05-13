<?php
/**
 * Class Component_Tests.
 *
 * @package WP_Irving
 */

use WP_Irving\Component;

/**
 * Tests for the component class.
 */
class Component_Tests extends WP_UnitTestCase {

	/**
	 * Test suite setup.
	 */
	public static function setUpBeforeClass() {
	}

	public function setUp() {
		parent::setUp();
	}

	/**
	 * Test the component constructor.
	 */
	public function test_component_constructor() {
	}

	public function test_name() {

		// Test name being set.
		$no_namespace      = new Component( 'example' );
		$correct_namespace = new Component( 'irving/example' );
		$too_many_slashes  = new Component( 'irving/layouts/example' );

		// Test namespaces.
		$this->assertEmpty( '', $no_namespace->get_namespace(), 'Expected an empty string.' );
		$this->assertEquals( 'irving', $correct_namespace->get_namespace() );
		$this->assertEquals( 'irving', $too_many_slashes->get_namespace() );
	}
}
