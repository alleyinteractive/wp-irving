<?php
/**
 * Class Component_Tests.
 *
 * @package WP_Irving
 */

use WP_Irving\Component;

/**
 * Tests for the component class.
 *
 * @group component
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
			$component = json_decode( $component, true );

			if ( is_null( $component ) ) {
				assert( false, 'Could not load ' . $file_name );
				continue;
			}

			self::$components[ str_replace( '.json', '', $file_name ) ] = ( new Component( $component['name'], $component ) )->hydrate_children();
		}
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
	 * Tests for the `get_name()` method.
	 */
	public function test_get_name() {
		$this->assertEquals( 'example', $this->get_component( 'name-only' )->get_name() );
		$this->assertEquals( 'irving/example', $this->get_component( 'basic-example' )->get_name() );
	}

	/**
	 * Tests for the `get_namespace()` method.
	 */
	public function test_get_namespace() {
		$this->assertEquals( '', $this->get_component( 'name-only' )->get_namespace(), 'Expected an empty string.' );
		$this->assertEquals( 'irving', $this->get_component( 'basic-example' )->get_namespace() );
	}

	/**
	 * Tests for the `set_name()` method.
	 */
	public function test_set_name() {

		// Get a copy of this testing component.
		$component = $this->get_component( 'name-only' );

		$component->set_name( 'testing-name' );
		$this->assertEquals( 'testing-name', $component->get_name() );

		$component->set_name( 'irving/with-a-namespace' );
		$this->assertEquals( 'irving/with-a-namespace', $component->get_name() );
	}

	/**
	 * Tests for the `get_config()` method.
	 */
	public function test_get_config() {

		// Get a copy of this testing component.
		$name_only     = $this->get_component( 'name-only' );
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
	 * Tests for the `get_config_by_key()` method.
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
	 * Tests for the `set_config()` method.
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
	 * Tests for the `merge_config()` method.
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
	 * Tests for the `set_config_by_key()` method.
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
	 * Tests for the `get_config_schema()` method.
	 */
	public function test_get_config_schema() {

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
	public function test_get_theme() {
		$this->assertEquals( 'primary', $this->get_component( 'theme-options' )->get_theme() );
	}

	/**
	 * Tests for the `set_theme()` method.
	 */
	public function test_set_theme() {

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
	 * Tests for the `get_theme_options()` method.
	 */
	public function test_get_theme_options() {
		$this->assertEquals(
			[
				'primary',
				'secondary',
			],
			$this->get_component( 'theme-options' )->get_theme_options()
		);
	}

	/**
	 * Tests for the `is_available_theme()` method.
	 */
	public function test_is_available_theme() {

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

		$this->assertEquals(
			$expected,
			$this->get_component( $slug )->jsonSerialize(),
			$message
		);
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

		$component = new Component( 'irving/text' );

		add_filter( 'wp_irving_serialize_component', [ $this, 'wp_irving_serialize_component_action' ] );

		$this->assertEquals(
			[
				'name'     => 'irving/text',
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

		$component = new Component( 'irving/text' );

		add_filter( 'wp_irving_serialize_component_array', [ $this, 'wp_irving_serialize_component_array_action' ] );

		$this->assertEquals(
			[
				'name'     => 'irving/text',
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
