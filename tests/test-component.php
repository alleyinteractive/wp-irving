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
	 * Associative array of components to use for testing.
	 *
	 * @var array
	 */
	public static $components = [];

	public static function setUpBeforeClass() {
		self::create_test_components();
	}

	/**
	 * Loop through the directory with example component data and initialize
	 * new class instances.
	 */
	public static function create_test_components() {

		// Loop through each file in the /component-data/ directory.
		foreach ( scandir( __DIR__ . '/component-data/' ) as $file_name ) {

			// Only worry about .json files.
			if ( false === strpos( $file_name, '.json' ) ) {
				continue;
			}

			// Validate the path.
			$path = sprintf( __DIR__ . '/component-data/%1$s', $file_name );
			if ( ! file_exists( $path ) ) {
				continue;
			}

			$component = file_get_contents( $path ); // phpcs:ignore
			$component = json_decode( $component, TRUE );

			if ( is_null( $component ) ) {
				assert( false, 'Could not load ' . $file_name );
				continue;
			}

			self::$components[ str_replace( '.json', '', $file_name ) ] = new Component( $component['name'], $component );
		}
	}

	public static function load_test_component() {

	}

	/**
	 * Get a test component by the name.
	 *
	 * @param string $name Name
	 * @return ?Component
	 */
	public function get_component( $name ) {
		if ( isset( self::$components[ $name ] ) ) {
			return self::$components[ $name ];
		}

		return assert( false, 'Could not load ' . $name );
	}

	/**
	 * Test the name functionality.
	 */
	public function test_name() {

		// Name.
		$this->assertEquals( 'example', $this->get_component( 'example-001' )->get_name() );
		$this->assertEquals( 'irving/example', $this->get_component( 'irving-namespace' )->get_name() );

		// Namespaces.
		$this->assertEquals( '', $this->get_component( 'example-001' )->get_namespace(), 'Expected an empty string.' );
		$this->assertEquals( 'irving', $this->get_component( 'irving-namespace' )->get_namespace() );
	}

	/**
	 * Test the children functionality.
	 *
	 * Methods to test:
	 * get_children()
	 * set_children()
	 * prepend_children()
	 * append_children()
	 * set_child()
	 * prepend_child()
	 * append_child()
	 * sanitize_children()
	 */
	public function test_children() {

		$parent = $this->get_component( 'children-test-001' );
		$child  = $this->get_component( 'children-test-002' );

		// get_children()
		// set_children()
		// prepend_children()

		// append_children()
		$parent->append_children( [ $child ] );
		$this->assertEquals( $child, $parent->get_children()[0] );

		// set_child()
		// prepend_child()
		// append_child()
		// sanitize_children()
	}

	/**
	 * Test the camel case functionality.
	 */
	public function test_camel_case() {

		// Camel case.
		$this->assertEquals( Component::camel_case( 'Foo Bar' ), 'fooBar' );
		$this->assertEquals( Component::camel_case( '--foo-bar--' ), 'fooBar' );
		$this->assertEquals( Component::camel_case( '__FOO_BAR__' ), 'fooBar' );

		// Recursively camel case the keys of an entire array.
		$snake_case_keys = [
			'hello_world'         => '',
			'testing_another_key' => '',
			'Upper Case'           => '',
			'recursive_test'      => [
				'hello_world_2' => '',
			],
		];

		$expected_camel_case_keys = [
			'helloWorld'        => '',
			'testingAnotherKey' => '',
			'upperCase'         => '',
			'recursiveTest'     => [
				'helloWorld2' => '',
			],
		];

		$camel_case_keys = ( new Component() )->camel_case_keys( $snake_case_keys );

		$this->assertEquals( $expected_camel_case_keys, $camel_case_keys, 'Top level keys don\'t match.' );
		$this->assertEquals( $expected_camel_case_keys['recursiveTest'], $camel_case_keys['recursiveTest'], 'Nested keys don\'t match.' );
	}
}
