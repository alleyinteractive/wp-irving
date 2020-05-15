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

	/**
	 * Class setup.
	 */
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

	/**
	 * Get a test component by the name.
	 *
	 * @param string $name Name
	 * @return ?Component
	 */
	public function get_component( $name ) {
		if ( isset( self::$components[ $name ] ) ) {
			return clone self::$components[ $name ];
		}

		return assert( false, 'Could not load ' . $name );
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_get_name() {
		$this->assertEquals( 'example', $this->get_component( 'name-only' )->get_name() );
		$this->assertEquals( 'irving/example', $this->get_component( 'basic-example' )->get_name() );
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_get_namespace() {
		$this->assertEquals( '', $this->get_component( 'name-only' )->get_namespace(), 'Expected an empty string.' );
		$this->assertEquals( 'irving', $this->get_component( 'basic-example' )->get_namespace() );
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_set_name() {

		// Get a copy of this testing component.
		$component = clone $this->get_component( 'name-only' );

		$component->set_name( 'testing-name' );
		$this->assertEquals( 'testing-name', $component->get_name() );

		$component->set_name( 'irving/with-a-namespace' );
		$this->assertEquals( 'irving/with-a-namespace', $component->get_name() );
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_get_config() {

		// Get a copy of this testing component.
		$name_only     = clone $this->get_component( 'name-only' );
		$basic_example = $this->get_component( 'basic-example' );

		// Test getting the entire array.
		$this->assertEquals( [], $name_only->get_config() );
		$this->assertEquals(
			[
				'align' => 'left',
				'width' => 'wide',
			],
			$basic_example->get_config()
		);

		// Get individual values.
		$this->assertEquals( 'left', $basic_example->get_config( 'align' ) );
		$this->assertEquals( 'wide', $basic_example->get_config( 'width' ) );

		// Key does not exist.
		$this->assertNull( $basic_example->get_config( 'no-key' ) );
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_get_config_by_key() {

		// Get a copy of this testing component.
		$basic_example = $this->get_component( 'basic-example' );

		// Get individual values.
		$this->assertEquals( 'left', $basic_example->get_config_by_key( 'align' ) );
		$this->assertEquals( 'wide', $basic_example->get_config_by_key( 'width' ) );

		// Key does not exist.
		$this->assertNull( $basic_example->get_config_by_key( 'no-key' ) );
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_set_config() {

		// Get a copy of this testing component.
		$basic_example = $this->get_component( 'basic-example' );

		// Test starting value.
		$this->assertEquals( 'left', $basic_example->get_config( 'align' ) );

		// Change value and confirm.
		$basic_example->set_config( 'align', 'right' );
		$this->assertEquals( 'right', $basic_example->get_config( 'align' ) );

		// Test setting an entire config.
		$basic_example->set_config(
			[
				'foo' => 'bar',
			]
		);
		$this->assertEquals( 'bar', $basic_example->get_config( 'foo' ) );
		$this->assertNull( $basic_example->get_config( 'align' ) ); // Was overridden.
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_merge_config() {

		// Get a copy of this testing component.
		$basic_example = $this->get_component( 'basic-example' );

		// Ensure we start with the right values.
		$this->assertEquals(
			[
				'align' => 'left',
				'width' => 'wide',
			],
			$basic_example->get_config(),
			'Base config is wrong.'
		);

		$basic_example->merge_config(
			[
				'align' => 'right',
				'foo'   => 'bar',
			]
		);

		// Ensure the merged values are correct.
		$this->assertEquals(
			[
				'align' => 'right',
				'foo'   => 'bar',
				'width' => 'wide',
			],
			$basic_example->get_config()
		);
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_set_config_by_key() {

		// Get a copy of this testing component.
		$basic_example = $this->get_component( 'basic-example' );

		// Test starting value.
		$this->assertEquals( 'left', $basic_example->get_config( 'align' ) );

		// Change value and confirm.
		$basic_example->set_config_by_key( 'align', 'right' );
		$this->assertEquals( 'right', $basic_example->get_config( 'align' ) );
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_get_children() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_set_children() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_prepend_children() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_append_children() {
		$parent = clone $this->get_component( 'children-test-001' );
		$child  = clone $this->get_component( 'children-test-002' );

		$parent->append_children( [ $child ] );
		$this->assertEquals( $child, $parent->get_children()[0] );
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_set_child() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_prepend_child() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_append_child() {
		$parent = clone $this->get_component( 'children-test-001' );
		$child  = clone $this->get_component( 'children-test-002' );

		$parent->append_child( $child );
		$this->assertEquals( $child, $parent->get_children()[0] );
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_sanitize_children() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_get_theme() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_set_theme() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_get_theme_options() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_add_theme_options() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_remove_theme_options() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_sanitize_theme_options() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_get_context_provider() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_set_context_provider() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_get_context_consumer() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_set_context_consumer() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_callback() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_camel_case_keys() {

		// Set of test keys
		$snake_case_keys = [
			'foo_bar'     => '',
			'Foo Bar'     => '',
			'--foo-bar--' => '',
			'__FOO_BAR__' => [
				'foo_bar' => '',
				'Foo Bar' => '',
				'--foo-bar--' => '',
				'__FOO_BAR__' => '',
			],
		];

		$expected_camel_case_keys = [
			'fooBar' => '',
			'fooBar' => '',
			'fooBar' => '',
			'fooBar' => [
				'fooBar' => '',
				'fooBar' => '',
				'fooBar' => '',
				'fooBar' => '',
			],
		];

		// Run the method.
		$camel_case_keys = ( new Component() )->camel_case_keys( $snake_case_keys );

		$this->assertEquals( $expected_camel_case_keys, $camel_case_keys, 'Top level keys don\'t match.' );
		// $this->assertEquals( $expected_camel_case_keys['recursiveTest'], $camel_case_keys['recursiveTest'], 'Nested keys don\'t match.' );
	}

	/**
	 * Tests for the `camel_case()` memthod.
	 */
	public function test_camel_case() {
		$this->assertEquals( Component::camel_case( 'foo_bar' ), 'fooBar' );
		$this->assertEquals( Component::camel_case( 'Foo Bar' ), 'fooBar' );
		$this->assertEquals( Component::camel_case( '--foo-bar--' ), 'fooBar' );
		$this->assertEquals( Component::camel_case( '__FOO_BAR__' ), 'fooBar' );
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_jsonSerialize() {
	}

	/**
	 * Tests for the `get_name()` method.
	 */
	public function test_to_array() {
	}
}
