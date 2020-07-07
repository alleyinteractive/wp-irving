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

			register_component_from_config( $path );
		}
	}

	/**
	 * Reset the registry after tests are completed.
	 */
	public static function reset_component_registry() {
		get_registry()->reset();
	}

	/**
	 * Tests default setup for a component.
	 */
	public function test_component_defaults() {
		$component = new Component( 'test/basic' );

		$this->assertSame( 'test/basic', $component->get_name(), 'Component name not set.' );
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
	 * Tests for the get_alias() method.
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
	 * Tests for the get_config() method.
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
	 * Tests for the get_config_by_key() method.
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
	 * Test get_theme_options().
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
	 * Test get_theme().
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
	 * Data provider for test_get_theme().
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
				'foo',
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
	 * Data provider for test_context_values_set().
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
			]
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
	 * Tests for the `jsonSerialize()` method, which only calls `to_array()`.
	 *
	 * @dataProvider get_components_to_serialize
	 * @covers Components::to_array
	 *
	 * @param array  $args     Component args.
	 * @param array  $expected Expected shape of serialized data.
	 * @param string $message  Optional. Error message on failure. Default ''.
	 */
	public function test_json_serialize( array $args, array $expected, string $message = '' ) {
		// phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode
		$this->assertEquals(
			json_encode( $expected ),
			json_encode( new Component( ...$args ) ),
			$message
		);
		// phpcs:enable
	}

	/**
	 * Data provider for test_json_serialize().
	 *
	 * @return array
	 */
	public function get_components_to_serialize() {

		/**
		 * Makes use of registered component types that are
		 * registered during the setUp() method, which is run
		 * after the data provider is executed, so we must
		 * return component args as the first param.
		 */
		return [
			// Basic component serialization.
			[
				[ 'test/basic' ],
				[
					'name'     => 'test/basic',
					'config'   => (object) [
						'themeName'    => 'default',
						'themeOptions' => [ 'default' ],
					],
					'children' => [],
				],
				'Could not verify basic serialization.',
			],
			// Basic with configuration.
			[
				[
					'test/basic',
					[
						'config' => [
							'foo' => 'bar',
						],
					],
				],
				[
					'name'     => 'test/basic',
					'config'   => (object) [
						'foo'          => 'bar',
						'themeName'    => 'default',
						'themeOptions' => [ 'default' ],
					],
					'children' => [],
				],
				'Could not verify basic serialization with config.',
			],
			// Aliasing.
			[
				[ 'test/alias' ],
				[
					'name'     => 'test/component',
					'config'   => (object) [
						'themeName'    => 'default',
						'themeOptions' => [ 'default' ],
					],
					'children' => [],
				],
				'Could not verify aliasing worked.',
			],
			// Config schema.
			[
				[ 'test/schema' ],
				[
					'name'     => 'test/schema',
					'config'   => (object) [
						'testDefault'  => 'default',
						'themeName'    => 'default',
						'themeOptions' => [ 'default' ],
					],
					'children' => [],
				],
				'Could not verify default schema worked.',
			],
			// Registered theme options.
			[
				[ 'test/theme-options' ],
				[
					'name'     => 'test/theme-options',
					'config'   => (object) [
						'themeName'    => 'default',
						'themeOptions' => [ 'primary', 'secondary' ],
					],
					'children' => [],
				],
				'Could not verify registered theme options.',
			],
		];
	}

	/**
	 * Tests for the component (array) filter in the jsonSerialize() method.
	 */
	public function test_json_serialize_component_array_filter() {
		$component = new Component( 'test/basic' );

		add_filter( 'wp_irving_serialize_component_array', [ $this, 'wp_irving_serialize_component_array_action' ] );

		$this->assertEquals(
			[
				'name'     => 'test/basic',
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
		$component = new Component( 'test/basic' );

		$this->assertEquals(
			[
				'name'     => 'test/basic',
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
