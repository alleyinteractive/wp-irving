<?php
/**
 * Class Test_Class_Component.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use stdClass;
use WP_UnitTestCase;

/**
 * Tests for the Component class.
 *
 * @group components
 */
class Test_Class_Component extends WP_UnitTestCase {

	/**
	 * Associative array of components to use for testing.
	 *
	 * @var array
	 */
	public static $components = [];

	/**
	 * Class setup.
	 */
	public static function wpSetUpBeforeClass() {
		self::register_test_components();
	}

	/**
	 * Class tear down.
	 */
	public static function wpTearDownAfterClass() {
		self::reset_component_registry();
	}

	/**
	 * Register test components from config.
	 */
	public static function register_test_components() {

		// Loop through each file in the /test-components/ directory.
		foreach ( scandir( __DIR__ . '/test-components/' ) as $file_name ) {

			// Only worry about .json files.
			if ( false === strpos( $file_name, '.json' ) ) {
				continue;
			}

			// Validate the path.
			$path = sprintf( __DIR__ . '/test-components/%1$s', $file_name );
			if ( ! file_exists( $path ) ) {
				continue;
			}

			get_registry()->register_component_from_config( $path );
		}
	}

	/**
	 * Reset the registry after tests are completed.
	 */
	public static function reset_component_registry() {
		get_registry()->reset();
	}

	/**
	 * Get a test component by the name.
	 *
	 * @param string $name Component name.
	 * @return ?Component
	 */
	public function get_component( $name ) {
		if ( isset( self::$components[ $name ] ) ) {
			return clone self::$components[ $name ];
		}

		return assert( false, 'Could not load ' . $name );
	}

	/**
	 * Tests default setup for a component.
	 */
	public function test_component_defaults() {
		$component = new Component( 'example/name' );

		$this->assertSame( 'example/name', $component->get_name(), 'Component name not set.' );
		$this->assertSame( '', $component->get_alias(), 'Default alias not null.' );
		$this->assertSame( [], $component->get_config(), 'Default config not empty.' );
		$this->assertSame( [], $component->get_children(), 'Default children not empty.' );
		$this->assertSame( 'default', $component->get_theme(), 'Default theme not set.' );
		$this->assertSame( [ 'default' ], $component->get_theme_options(), 'Default theme options not set.' );
		$this->assertSame( [], $component->get_context(), 'Default context values not set.' );
		$this->assertNull( $component->get_callback( 'config' ), 'Default config callback not null.' );
		$this->assertNull( $component->get_callback( 'children' ), 'Default children callback not null.' );
		$this->assertNull( $component->get_callback( 'visibility' ), 'Default visibility callback not null.' );
	}

	/**
	 * Tests for the `get_namespace()` method.
	 */
	public function test_get_namespace() {
		$component = new Component( 'namespace/example' );

		$this->assertSame( 'namespace', $component->get_namespace() );
	}

	/**
	 * Tests for the `set_name()` method.
	 */
	public function test_set_name() {
		$this->markTestSkipped(
			'Private method.'
		);

		// Get a copy of this testing component.
		$component = $this->get_component( 'name-only' );

		$component->set_name( 'testing-name' );
		$this->assertEquals( 'testing-name', $component->get_name() );

		$component->set_name( 'irving/with-a-namespace' );
		$this->assertEquals( 'irving/with-a-namespace', $component->get_name() );
	}

	/**
	 * Tests for the `get_alias()` method.
	 */
	public function test_get_alias() {
		// "test/alias" is a registered component type.
		$component = new Component( 'test/alias' );

		$this->assertEquals(
			'test/component',
			$component->get_alias(),
			'Not able to get the expected alias.'
		);
	}

	/**
	 * Tests for the `get_config()` method.
	 */
	public function test_get_config() {
		$component = new Component(
			'example/config',
			[
				'config' => [
					'align' => 'left',
					'width' => 'wide',
				],
			]
		);

		// Get individual values.
		$this->assertEquals( 'left', $component->get_config( 'align' ) );
		$this->assertEquals( 'wide', $component->get_config( 'width' ) );

		// Key does not exist.
		$this->assertNull( $component->get_config( 'no-key' ) );
	}

	/**
	 * Tests setting config against a registered schema.
	 *
	 * @dataProvider get_config_for_test_schema
	 *
	 * @param array  $config   Config used to setup component.
	 * @param array  $expected Expected config values.
	 * @param string $message  Optional. Failure message.
	 */
	public function test_config_with_schema( $config, $expected, $message = '' ) {
		$component = new Component(
			'test/schema',
			[ 'config' => $config ]
		);

		$this->assertSame( $expected, $component->get_config(), $message );
	}

	/**
	 * Data provider for test_config_with_schema().
	 *
	 * @return array[] Array of config values and expected output.
	 */
	public function get_config_for_test_schema(): array {
		// Create an object used for testing.
		$object = new stdClass();

		return [
			// Empty config.
			[
				[],
				[ 'test-default' => 'default' ],
				'Default config values not set.',
			],
			// Override defaults.
			[
				[ 'test-default' => 'overridden' ],
				[ 'test-default' => 'overridden' ],
				'Default config values not overridden.',
			],
			// Test type checking.
			[
				[
					'test-array'  => [ 'value' ],
					'test-bool'   => true,
					'test-int'    => 999,
					'test-number' => 9.9,
					'test-object' => $object,
					'test-string' => 'value',
					// Values using unsupported types are allowed.
					'test-foo'    => 'value',
				],
				[
					'test-default' => 'default',
					'test-array'   => [ 'value' ],
					'test-bool'    => true,
					'test-int'     => 999,
					'test-number'  => 9.9,
					'test-object'  => $object,
					'test-string'  => 'value',
					'test-foo'     => 'value',
				],
				'Setting a typed values not working.',
			],
			// Test type checking failure.
			[
				[
					'test-array'  => 'wrong',
					'test-bool'   => 'wrong',
					'test-int'    => 'wrong',
					'test-number' => 'wrong',
					'test-object' => 'wrong',
					'test-string' => false,
				],
				[
					'test-default' => 'default',
				],
				'Setting an incorrectly typed values not working.',
			],
		];
	}

	/**
	 * Tests for the `get_config_by_key()` method.
	 */
	public function test_get_config_by_key() {
		$component = new Component(
			'example/config',
			[
				'config' => [
					'foo' => 'bar',
				],
			]
		);

		// Get individual values.
		$this->assertEquals(
			'bar',
			$component->get_config_by_key( 'foo' ),
			'Could not get config by key.'
		);

		// Key does not exist.
		$this->assertNull(
			$component->get_config_by_key( 'baz' )
		);
	}

	/**
	 * Test get_theme_options()
	 */
	public function test_get_theme_options() {
		// 'test/theme-options' is a registered component type.
		$component = new Component( 'test/theme-options' );

		$this->assertSame(
			[ 'primary', 'secondary' ],
			$component->get_theme_options(),
			'Did not get expected theme options.'
		);
	}

	/**
	 * Test get_theme()
	 *
	 * @dataProvider provide_get_theme_data
	 *
	 * @param array  $args     Arguments used to setup component.
	 * @param array  $expected Expected theme value.
	 * @param string $message  Optional. Failure message.
	 */
	public function test_get_theme_from_registered_options( $args, $expected, $message = '' ) {
		// 'test/theme-options' is a registered component type.
		$component = new Component( 'test/theme-options', $args );

		$this->assertSame(
			$expected,
			$component->get_theme(),
			$message
		);
	}

	/**
	 * Data provider for test_get_theme
	 *
	 * @return array[] Array of test arguments.
	 */
	public function provide_get_theme_data() {
		return [
			[
				[],
				'default',
				'Default theme not correct.',
			],
			[
				[ 'theme' => 'foo' ],
				'default',
				'Invalid theme should fall back to defaults.',
			],
			[
				[ 'theme' => 'primary' ],
				'primary',
				'Primary theme not correct.',
			],
			[
				[ 'theme' => 'secondary' ],
				'secondary',
				'Secondary theme not correct.',
			],
		];
	}

	/**
	 * Test that context is applied correctly.
	 *
	 * @group context
	 */
	public function test_get_context() {
		get_context_store()->set(
			[ 'test/foo' => 'bar' ]
		);

		// 'test/use-context' is a registered type.
		$component = new Component( 'test/use-context' );

		// Clean up.
		get_context_store()->reset();

		$this->assertSame(
			[
				'test/foo' => 'bar',
				'test/baz' => null,
			],
			$component->get_context(),
			'Calculated context values not set.'
		);
	}

	/**
	 * Test that context is applied correctly.
	 *
	 * @dataProvider provide_context_config_data
	 * @group context
	 *
	 * @param array $config   Test config values.
	 * @param array $expected Expected value.
	 */
	public function test_context_values_set( $config, $expected ) {
		get_context_store()->set(
			[ 'test/foo' => 'bar' ]
		);

		// 'test/use-context' is a registered type.
		$component = new Component(
			'test/use-context',
			[ 'config' => $config ]
		);

		// Clean up.
		get_context_store()->reset();

		$this->assertSame(
			$expected,
			$component->get_config()
		);
	}

	/**
	 * Data provider for test_context_values_set
	 *
	 * @return array[]
	 */
	public function provide_context_config_data() {
		return [
			[
				[],
				[
					'foo' => 'bar',
					'baz' => 'default',
				],
			],
			[
				[
					'foo' => 'overridden',
				],
				[
					'foo' => 'overridden',
					'baz' => 'default',
				],
			],
		];
	}

	/**
	 * Test config callbacks.
	 */
	public function test_config_callback() {
		$name = 'example/component';

		/*
		 * Register component with callback.
		 *
		 * We're using a callback that uses the config in order
		 * to ensure that the callback is passed an array.
		 */
		get_registry()->register_component(
			$name,
			[
				'config_callback' => function ( array $config ) {
					return array_merge(
						[ 'foo' => 'bar' ],
						$config
					);
				},
			]
		);

		$component = new Component(
			$name,
			[
				'config' => [
					'baz' => 'qux',
				],
			]
		);

		// Clean up.
		get_registry()->unregister_component( $name );

		$this->assertEquals(
			[
				'foo' => 'bar',
				'baz' => 'qux',
			],
			$component->get_config()
		);
	}

	/**
	 * Test children set from config.
	 */
	public function test_children_from_config() {
		// Test both array and string notation.
		$component = new Component(
			'example/parent',
			[
				'children' => [
					[ 'example/child-1' ],
					'example/child-2',
				],
			],
		);

		// Children should return as Component classes.
		$this->assertEquals(
			[
				new Component( 'example/child-1' ),
				new Component( 'example/child-2' ),
			],
			$component->get_children()
		);
	}

	/**
	 * Tests for the `set_config()` method.
	 */
	public function test_set_config() {
		$this->markTestSkipped(
			'The set_config() method is now private.'
		);

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
	 * Tests for the `merge_config()` method.
	 */
	public function test_merge_config() {
		$this->markTestSkipped(
			'The merge_config() method is now private.'
		);

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
	 * Tests for the `set_config_by_key()` method.
	 */
	public function test_set_config_by_key() {
		$this->markTestSkipped(
			'The set_config_by_key() method is now private.'
		);

		// Get a copy of this testing component.
		$basic_example = $this->get_component( 'basic-example' );

		// Test starting value.
		$this->assertEquals( 'left', $basic_example->get_config( 'align' ) );

		// Change value and confirm.
		$basic_example->set_config_by_key( 'align', 'right' );
		$this->assertEquals( 'right', $basic_example->get_config( 'align' ) );
	}

	/**
	 * Tests for the `get_config_schema()` method.
	 */
	public function test_get_config_schema() {
		$this->markTestSkipped(
			'The get_config_schema() method is now private.'
		);

		$schema_example = $this->get_component( 'schema' );

		$expects = [
			'wp_query' => [
				'default' => null,
				'hidden'  => true,
				'type'    => 'object',
			],
		];

		$this->assertEquals( $expects, $schema_example->get_config_schema(), 'Config schema did not match what was expected.' );
	}

	/**
	 * Tests for the `set_config_schema()` method.
	 */
	public function test_set_config_schema() {
		$this->markTestSkipped(
			'The set_config_schema() method is now private.'
		);

		$new_schema = [
			'post' => [],
		];

		$expects = [
			'post' => [
				'default' => null,
				'hidden'  => false,
				'type'    => 'null',
			],
		];

		$schema_example = $this->get_component( 'schema' );

		$schema_example->set_config_schema( $new_schema );

		$this->assertEquals( $expects, $schema_example->get_config_schema(), 'Schema did not match what was expected.' );
	}

	/**
	 * Tests for the `get_children()` method.
	 */
	public function test_get_children() {
		$this->markTestSkipped(
			'The get_children() method is now private.'
		);

		// Scaffold three new basic components.
		$expected_components = [
			new Component( 'parent-child-001' ),
			new Component( 'parent-child-002' ),
			new Component( 'parent-child-003' ),
		];

		// Test on a component with three children.
		$this->assertEquals( $expected_components, $this->get_component( 'children-test-001' )->get_children() );

		// Test on a component with no children.
		$this->assertEquals( [], $this->get_component( 'name-only' )->get_children() );
	}

	/**
	 * Tests for the `set_children()` method.
	 */
	public function test_set_children() {
		$this->markTestSkipped(
			'The set_children() method is now private.'
		);

		// Get components.
		$parent   = $this->get_component( 'children-test-001' );
		$child_01 = $this->get_component( 'children-test-002' );
		$child_02 = $this->get_component( 'children-test-003' );

		// Scaffold three new basic components.
		$expected_components = [
			new Component( 'parent-child-001' ),
			new Component( 'parent-child-002' ),
			new Component( 'parent-child-003' ),
		];

		// Ensure the data loaded is correct to begin with.
		$this->assertEquals( $expected_components, $parent->get_children() );

		// Run method and check.
		$parent->set_children( [ $child_01, $child_02 ] );
		$this->assertEquals(
			[
				$child_01,
				$child_02,
			],
			$parent->get_children()
		);
	}

	/**
	 * Tests for the `prepend_children()` method.
	 */
	public function test_prepend_children() {
		$this->markTestSkipped(
			'The prepend_children() method is now private.'
		);

		// Get components to use.
		$parent   = $this->get_component( 'children-test-001' );
		$child_01 = $this->get_component( 'children-test-002' );
		$child_02 = $this->get_component( 'children-test-003' );

		// Build an array of children.
		$children = [
			$child_01,
			$child_02,
		];

		// Prepend children to fill the 1st and 2nd slots.
		$parent->prepend_children( $children );
		$this->assertEquals( $child_01, $parent->get_children()[0] );
		$this->assertEquals( $child_02, $parent->get_children()[1] );

		// Reset children.
		$parent->set_children( [] );
		$parent->prepend_children( $children );
		$this->assertEquals( $child_01, $parent->get_children()[0] );
		$this->assertEquals( $child_02, $parent->get_children()[1] );
	}

	/**
	 * Tests for the `append_children()` method.
	 */
	public function test_append_children() {
		$this->markTestSkipped(
			'The append_children() method is now private.'
		);

		// Get components to use.
		$parent   = $this->get_component( 'children-test-001' );
		$child_01 = $this->get_component( 'children-test-002' );
		$child_02 = $this->get_component( 'children-test-003' );

		// Build an array of children.
		$children = [
			$child_01,
			$child_02,
		];

		// Append children to fill the 4th and 5th slots.
		$parent->append_children( $children );
		$this->assertEquals( $child_01, $parent->get_children()[3] );
		$this->assertEquals( $child_02, $parent->get_children()[4] );

		// Reset children.
		$parent->set_children( [] );
		$parent->append_children( $children );
		$this->assertEquals( $child_01, $parent->get_children()[0] );
		$this->assertEquals( $child_02, $parent->get_children()[1] );
	}

	/**
	 * Tests for the `set_child()` method.
	 */
	public function test_set_child() {
		$this->markTestSkipped(
			'The set_child() method is now private.'
		);

		// Get components.
		$parent   = $this->get_component( 'children-test-001' );
		$child_01 = $this->get_component( 'children-test-002' );
		$child_02 = $this->get_component( 'children-test-003' );

		// Scaffold three new basic components.
		$expected_components = [
			new Component( 'parent-child-001' ),
			new Component( 'parent-child-002' ),
			new Component( 'parent-child-003' ),
		];

		// Ensure the data loaded is correct to begin with.
		$this->assertEquals( $expected_components, $parent->get_children() );

		// Run method and check.
		$parent->set_child( $child_01 );
		$this->assertEquals( [ $child_01 ], $parent->get_children() );

		// Run method and check.
		$parent->set_child( $child_02 );
		$this->assertEquals( [ $child_02 ], $parent->get_children() );
	}

	/**
	 * Tests for the `prepend_child()` method.
	 */
	public function test_prepend_child() {
		$this->markTestSkipped(
			'The prepend_child() method is now private.'
		);

		// Get components to use.
		$parent   = $this->get_component( 'children-test-001' );
		$child_01 = $this->get_component( 'children-test-002' );
		$child_02 = $this->get_component( 'children-test-003' );

		// Prepend the first child as the 0th slot.
		$parent->prepend_child( $child_01 );
		$this->assertEquals( $child_01, $parent->get_children()[0] );

		// Prepend the second child as the 0th slot, forcing the first into the
		// 1st slot.
		$parent->prepend_child( $child_02 );
		$this->assertEquals( $child_02, $parent->get_children()[0] );
		$this->assertEquals( $child_01, $parent->get_children()[1] );

		// Clear the children.
		$parent->set_children( [] );

		// Prepend the first child as the 0th slot.
		$parent->prepend_child( $child_01 );
		$this->assertEquals( $child_01, $parent->get_children()[0] );

		// Prepend the second child as the 0th slot, forcing the first into the
		// 1st slot.
		$parent->prepend_child( $child_02 );
		$this->assertEquals( $child_02, $parent->get_children()[0] );
		$this->assertEquals( $child_01, $parent->get_children()[1] );
	}

	/**
	 * Tests for the `append_child()` method.
	 */
	public function test_append_child() {
		$this->markTestSkipped(
			'The append_child() method is now private.'
		);

		// Get components to use.
		$parent   = $this->get_component( 'children-test-001' );
		$child_01 = $this->get_component( 'children-test-002' );
		$child_02 = $this->get_component( 'children-test-003' );

		// Append the child as the 4th slot.
		$parent->append_child( $child_01 );
		$parent->append_child( $child_02 );
		$this->assertEquals( $child_01, $parent->get_children()[3] );
		$this->assertEquals( $child_02, $parent->get_children()[4] );

		// Clear the children.
		$parent->set_children( [] );

		// Append a child to the empty array.
		$parent->append_child( $child_01 );
		$parent->append_child( $child_02 );
		$this->assertEquals( $child_01, $parent->get_children()[0] );
		$this->assertEquals( $child_02, $parent->get_children()[1] );
	}

	/**
	 * Tests for the `reset_array()` method.
	 */
	public function test_reset_array() {
		$this->markTestSkipped(
			'This method is now private.'
		);

		$example_data = [
			'test',
			'',
			'test two',
			[],
		];

		// Test removal of empty elements.
		$this->assertEquals( [ 'test', 'test two' ], Component::reset_array( $example_data ) );

		// Resets when an array value has been unset.
		unset( $example_data[0] );
		$this->assertEquals( [ 'test two' ], Component::reset_array( $example_data ) );
	}

	/**
	 * Tests for the `get_theme()` method.
	 */
	public function _test_get_theme() {
		$this->markTestSkipped(
			'This method is now private.'
		);

		$this->assertEquals( 'primary', $this->get_component( 'theme-options' )->get_theme() );
	}

	/**
	 * Tests for the `set_theme()` method.
	 */
	public function test_set_theme() {
		$this->markTestSkipped(
			'This method is now private.'
		);

		// Get a copy of this testing component.
		$component = $this->get_component( 'theme-options' );

		// Test a theme in the options.
		$component->set_theme( 'secondary' );
		$this->assertEquals( 'secondary', $component->get_theme() );

		// This won't work, because `not-a-theme` isn't in the options array.
		$component->set_theme( 'not-a-theme' );
		$this->assertEquals( 'secondary', $component->get_theme() );

		// Bypass the check.
		$component->set_theme( 'forced-theme', true );
		$this->assertEquals( 'forced-theme', $component->get_theme() );
	}

	/**
	 * Tests for the `is_available_theme()` method.
	 */
	public function test_is_available_theme() {
		$this->markTestSkipped(
			'This method is now private.'
		);

		// Get a copy of this testing component.
		$component = $this->get_component( 'theme-options' );

		$this->assertEquals( true, $component->is_available_theme( 'primary' ) );
		$this->assertEquals( true, $component->is_available_theme( 'secondary' ) );
		$this->assertEquals( false, $component->is_available_theme( 'large' ) );
	}

	/**
	 * Tests for the `set_theme_options()` method.
	 */
	public function test_set_theme_options() {
		$this->markTestSkipped(
			'This method is now private.'
		);

		// Get a copy of this testing component.
		$component = $this->get_component( 'theme-options' );

		$component->set_theme_options( [ 'one', 'two' ] );

		$this->assertEquals(
			[
				'one',
				'two',
			],
			$component->get_theme_options()
		);
	}

	/**
	 * Tests for the `add_theme_options()` method.
	 */
	public function test_add_theme_options() {
		$this->markTestSkipped(
			'This method is now private.'
		);

		// Get a copy of this testing component.
		$component = $this->get_component( 'theme-options' );

		// Test adding one.
		$component->add_theme_options( [ 'large' ] );

		$this->assertEquals(
			[
				'primary',
				'secondary',
				'large',
			],
			$component->get_theme_options(),
			'Could not confirm adding single theme option.'
		);

		// Test adding two.
		$component->add_theme_options( [ 'extra-large', 'double-extra-large' ] );

		$this->assertEquals(
			[
				'primary',
				'secondary',
				'large',
				'extra-large',
				'double-extra-large',
			],
			$component->get_theme_options(),
			'Could not confirm adding multiple theme options.'
		);
	}

	/**
	 * Tests for the `add_theme_option()` method.
	 */
	public function test_add_theme_option() {
		$this->markTestSkipped(
			'This method is now private.'
		);

		// Get a copy of this testing component.
		$component = $this->get_component( 'theme-options' );

		$component->add_theme_option( 'large' );

		$this->assertEquals(
			[
				'primary',
				'secondary',
				'large',
			],
			$component->get_theme_options()
		);

		// Ensure no duplicates.
		$component->add_theme_option( 'large' );

		$this->assertEquals(
			[
				'primary',
				'secondary',
				'large',
			],
			$component->get_theme_options(),
			'Could not ensure duplicate values are not added.'
		);
	}

	/**
	 * Tests for the `remove_theme_options()` method.
	 *
	 * @dataProvider get_theme_options_to_remove
	 *
	 * @param array  $options  List of theme options.
	 * @param array  $expected Expected remaining options after removal.
	 * @param string $message  Optional. Message to display when assertion fails. Default none.
	 */
	public function test_remove_theme_options( array $options, array $expected, string $message = '' ) {
		$this->markTestSkipped(
			'This method is now private.'
		);

		// Get a copy of this testing component.
		$component = $this->get_component( 'theme-options' );

		$component->remove_theme_options( $options );

		$this->assertEquals( $expected, $component->get_theme_options(), $message );
	}

	/**
	 * Data provider for 'test_remove_theme_options'
	 *
	 * @return array
	 */
	public function get_theme_options_to_remove() {
		return [
			[
				[ 'primary' ],
				[ 'secondary' ],
				'Could not confirm single options removed.',
			],
			[
				[ 'primary', 'secondary' ],
				[],
				'Could not confirm multiple options removed.',
			],
		];
	}

	/**
	 * Tests for the `remove_theme_option()` method.
	 */
	public function test_remove_theme_option() {
		$this->markTestSkipped(
			'This method is now private.'
		);

		// Get a copy of this testing component.
		$component = $this->get_component( 'theme-options' );

		// Remove a theme.
		$component->remove_theme_option( 'secondary' );

		$this->assertEquals(
			[
				'primary',
			],
			$component->get_theme_options()
		);
	}

	/**
	 * Tests for the `callback()` method.
	 */
	public function test_callback() {
		$this->markTestSkipped(
			'This method is now private.'
		);

		// Get a copy of this testing component.
		$component = $this->get_component( 'basic-example' );

		// Execute a callback, using a closure as the callable.
		$component->callback(
			function( $component, $extra_param ) {
				$component->set_config( 'foo', $extra_param );
				return $component;
			},
			'bar' // Extra param that can be passed to the callback.
		);

		$this->assertEquals( 'bar', $component->get_config( 'foo' ) );
	}

	/**
	 * Tests for the `camel_case_keys()` method.
	 *
	 * @group camel
	 */
	public function test_camel_case_keys() {
		$this->markTestSkipped(
			'This method is now private.'
		);

		// Set of test keys.
		$snake_case_keys = [
			'foo_bar'     => '',
			'Foo Bar'     => '',
			'--foo-bar--' => '',
			'__FOO_BAR__' => [
				'foo_bar'     => '',
				'Foo Bar'     => '',
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
		$camel_case_keys = Component::camel_case_keys( $snake_case_keys );

		$this->assertEquals( $expected_camel_case_keys, $camel_case_keys, 'Top level keys don\'t match.' );
	}

	/**
	 * Tests for the `camel_case()` method.
	 *
	 * @group camel
	 */
	public function test_camel_case() {
		$this->markTestSkipped(
			'This method is now private.'
		);

		$this->assertEquals( Component::camel_case( 'foo_bar' ), 'fooBar' );
		$this->assertEquals( Component::camel_case( 'Foo Bar' ), 'fooBar' );
		$this->assertEquals( Component::camel_case( '--foo-bar--' ), 'fooBar' );
		$this->assertEquals( Component::camel_case( '__FOO_BAR__' ), 'fooBar' );
	}

	/**
	 * Tests for the `jsonSerialize()` method, which only calls `to_array()`.
	 *
	 * @dataProvider get_components_to_serialize
	 * @covers Components::to_array
	 *
	 * @param string $slug     Component slug to load.
	 * @param array  $expected Expected shape of serialized data.
	 * @param string $message  Optional. Error message on failure. Default ''.
	 */
	public function test_json_serialize( string $slug, array $expected, string $message = '' ) {
		$this->markTestIncomplete( 'Need to fix this.' );

		// phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode
		$this->assertEquals(
			json_encode( $expected ),
			json_encode( $this->get_component( $slug ) ),
			$message
		);
		// phpcs:enable
	}

	/**
	 * Data provider for 'test_json_serialize'
	 *
	 * @return array
	 */
	public function get_components_to_serialize() {
		return [
			[
				'basic-example',
				[
					'name'     => 'irving/example',
					'_alias'   => '',
					'config'   => (object) [
						'align'        => 'left',
						'themeName'    => 'default',
						'themeOptions' => [
							'default',
						],
						'width'        => 'wide',
					],
					'children' => [],
				],
			],
			[
				'children-test-001',
				[
					'name'     => 'parent-example',
					'_alias'   => '',
					'config'   => (object) [
						'themeName'    => 'default',
						'themeOptions' => [
							'default',
						],
					],
					'children' => [
						new Component( 'parent-child-001' ),
						new Component( 'parent-child-002' ),
						new Component( 'parent-child-003' ),
					],
				],
			],
			[
				'theme-options',
				[
					'name'     => 'example',
					'_alias'   => '',
					'config'   => (object) [
						'themeName'    => 'primary',
						'themeOptions' => [
							'primary',
							'secondary',
						],
					],
					'children' => [],
				],
			],
			[
				'schema',
				[
					'name'     => 'irving/schema',
					'_alias'   => '',
					'config'   => (object) [
						'wpQuery'      => null,
						'themeName'    => 'default',
						'themeOptions' => [
							'default',
						],
					],
					'children' => [],
				],
			],
		];
	}

	/**
	 * Tests for the component filter in the `jsonSerialize()` method.
	 */
	public function test_json_serialize_component_filter() {
		$this->markTestSkipped(
			'Filtering serialization must be fixed.'
		);

		$component = new Component( 'irving/text' );

		add_filter( 'wp_irving_serialize_component', [ $this, 'wp_irving_serialize_component_action' ] );

		$this->assertEquals(
			[
				'name'     => 'irving/text',
				'_alias'   => '',
				'config'   => (object) [
					'test'         => true,
					'themeName'    => 'default',
					'themeOptions' => [
						'default',
					],
				],
				'children' => [],
			],
			$component->jsonSerialize(),
			'Component serialization filter did not modify data correctly.'
		);

		remove_filter( 'wp_irving_serialize_component', [ $this, 'wp_irving_serialize_component_action' ] );

		// Reset to test again.
		$component = new Component( 'irving/text' );

		$this->assertEquals(
			[
				'name'     => 'irving/text',
				'_alias'   => '',
				'config'   => (object) [
					'themeName'    => 'default',
					'themeOptions' => [
						'default',
					],
				],
				'children' => [],
			],
			$component->jsonSerialize(),
			'Component serialization filter was not removed correctly.'
		);
	}

	/**
	 * Modify the config value of a component using a filter.
	 *
	 * @param Component $component Component instance.
	 * @return Component
	 */
	public function wp_irving_serialize_component_action( Component $component ): Component {
		return $component->set_config( 'test', true );
	}
	/**
	 * Tests for the component (array) filter in the `jsonSerialize()` method.
	 */
	public function test_json_serialize_component_array_filter() {
		$this->markTestSkipped(
			'Filtering serialization must be fixed.'
		);

		$component = new Component( 'irving/text' );

		add_filter( 'wp_irving_serialize_component_array', [ $this, 'wp_irving_serialize_component_array_action' ] );

		$this->assertEquals(
			[
				'name'     => 'irving/text',
				'_alias'   => '',
				'config'   => (object) [
					'test'         => true,
					'themeName'    => 'default',
					'themeOptions' => [
						'default',
					],
				],
				'children' => [],
			],
			$component->jsonSerialize(),
			'Component serialization filter did not modify data correctly.'
		);

		remove_filter( 'wp_irving_serialize_component_array', [ $this, 'wp_irving_serialize_component_array_action' ] );

		// Reset to test again.
		$component = new Component( 'irving/text' );

		$this->assertEquals(
			[
				'name'     => 'irving/text',
				'_alias'   => '',
				'config'   => (object) [
					'themeName'    => 'default',
					'themeOptions' => [
						'default',
					],
				],
				'children' => [],
			],
			$component->jsonSerialize(),
			'Component serialization filter was not removed correctly.'
		);
	}

	/**
	 * Modify the config value of a component (as an array) using a filter.
	 *
	 * @param array $component Component (as an array).
	 * @return array
	 */
	public function wp_irving_serialize_component_array_action( array $component ): array {
		$component['config']->test = true;
		return $component;
	}

}
