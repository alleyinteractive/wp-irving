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

	public function setUp() {
		parent::setUp();
		$this->create_test_components();
	}

	/**
	 * Loop through the directory with example component data and initialize
	 * new class instances.
	 */
	public function create_test_components() {

		// Loop through each file in the /component-data/ directory.
		foreach ( scandir( __DIR__ . '/component-data/' ) as $file_name ) {

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
}
