<?php
/**
 * Class Test_Templates
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

use WP_Irving\Components;
use WP_Irving\Components\Component;
use WP_UnitTestCase;

/**
 * Test templates functionality.
 *
 * @group templates
 */
class Test_Templates extends WP_UnitTestCase {

	/**
	 * Set up test data.
	 */
	public function setup() {
		parent::setUp();

		// Hook up template path filters.
		add_filter(
			'wp_irving_template_path',
			function () {
				return dirname( __FILE__ ) . '/templates';
			}
		);

		add_filter(
			'wp_irving_template_part_path',
			function () {
				return dirname( __FILE__ ) . '/template-parts';
			}
		);
	}

	/**
	 * Data provider for test_template_paths.
	 *
	 * @return array
	 */
	public function get_template_paths() {
		return [
			[
				[ 'defaults' ],
			],
			[
				[ 'index' ],
			],
			[
				[ 'single' ],
			],
		];
	}

	/**
	 * Test that local template paths is working.
	 *
	 * @dataProvider get_template_paths
	 *
	 * @param array $paths Template path slugs.
	 */
	public function test_template_paths( $paths ) {
		$path = locate_template( $paths );

		$this->assertTrue( file_exists( $path ) );
	}

	/**
	 * Test that local template partial paths is working.
	 */
	public function test_template_partial_paths() {
		$path = locate_template_part( 'sidebar' );

		$this->assertTrue( file_exists( $path ) );
	}

	/**
	 * Test data hydration.
	 *
	 * @dataProvider get_template_test_cases
	 * @group hydration
	 *
	 * @param array  $data     Test data to hydrate.
	 * @param array  $expected Expected result from hydration.
	 * @param string $message  Optional. Failure message.
	 */
	public function test_hydrate_template( $data, $expected, $message = '' ) {
		$this->assertEquals( $expected, hydrate_template( $data ), $message );
	}

	/**
	 * Data provider for template hydration tests.
	 *
	 * @return array[] List of params.
	 */
	public function get_template_test_cases() {
		return [
			// Basic hydration.
			[
				// Template.
				[
					[ 'name' => 'test/component' ],
					[ 'name' => 'test/component' ],
				],
				// Expected.
				[
					new Component( 'test/component' ),
					new Component( 'test/component' ),
				],
				'Could not confirm template hydration.',
			],
			// Basic parent child.
			[
				// Template.
				[
					[
						'name'     => 'test/parent',
						'config'   => [],
						'children' => [
							[
								'name'   => 'test/child',
								'config' => [],
							],
						],
					],
				],
				// Expected.
				[
					new Component(
						'test/parent',
						[
							'children' => [
								[ 'name' => 'test/child' ],
							],
						]
					),
				],
				'Could not confirm parent/child hydration.',
			],
			// Basic template part hydration.
			// Loaded from the template-parts/example.json.
			[
				// Template.
				[
					[ 'name' => 'template-part/example' ],
				],
				// Expected.
				[
					new Component( 'example/component1' ),
					new Component(
						'example/component2',
						[
							'children' => [
								[ 'name' => 'example/component3' ],
							],
						]
					),
				],
				'Template parts are not hydrating correctly.',
			],
			// Nested template part hydration.
			// Loaded from the template-parts/example-nested.json.
			[
				// Template.
				[
					[ 'name' => 'template-part/example-nested' ],
				],
				// Expected.
				[
					new Component( 'example/component' ),
					new Component( 'example/component1' ),
					new Component(
						'example/component2',
						[
							'children' => [
								[ 'name' => 'example/component3' ],
							],
						]
					),
					new Component( 'example/component' ),
				],
				'Nested template parts are not hydrating correctly.',
			],
		];
	}

	/**
	 * Test for prepare_data_from_template.
	 *
	 * @group hydration
	 */
	public function test_prepare_data_from_template() {
		$expected = [
			'defaults' => [
				new Component( 'test/component' ),
			],
			'page'     => [
				new Component( 'test/component' ),
			],
		];

		$template_path = locate_template( [ 'test-simple' ] );

		$data = prepare_data_from_template( $template_path );

		$this->assertEquals( $expected, $data );
	}

	/**
	 * Test template hydration uses context.
	 *
	 * @group context
	 */
	public function test_components_use_context() {
		Components\get_registry()->register_component(
			'provider',
			[
				'config'           => [
					'prop_with_default'            => [
						'type'    => 'text',
						'default' => 'default value',
					],
					'prop_with_default_overridden' => [
						'type'    => 'number',
						'default' => 10,
					],
					'prop_without_default'         => [
						'type' => 'text',
					],
				],
				'provides_context' => [
					'test/with_default'            => 'prop_with_default',
					'test/with_default_overridden' => 'prop_with_default_overridden',
					'test/without_default'         => 'prop_without_default',
				],
			]
		);

		Components\get_registry()->register_component(
			'consumer',
			[
				'config'      => [
					'prop_with_default'            => [
						'type' => 'text',
					],
					'prop_with_default_overridden' => [
						'type' => 'number',
					],
					'prop_without_default'         => [
						'type' => 'text',
					],
				],
				'use_context' => [
					'test/with_default'            => 'prop_with_default',
					'test/with_default_overridden' => 'prop_with_default_overridden',
					'test/without_default'         => 'prop_without_default',
				],
			]
		);

		$template = [
			[
				'name'     => 'provider',
				'config'   => [
					'prop_with_default_overridden' => 20,
					'prop_without_default'         => 'test value',
				],
				'children' => [
					[
						'name' => 'consumer',
					],
				],
			],
		];

		$hydrated = json_decode( wp_json_encode( hydrate_template( $template ) ), true );

		$expected = [
			[
				'name'     => 'provider',
				'_alias'   => '',
				'config'   => [
					'propWithDefault'           => 'default value',
					'propWithDefaultOverridden' => 20,
					'propWithoutDefault'        => 'test value',
					'themeName'                 => 'default',
					'themeOptions'              => [ 'default' ],
				],
				'children' => [
					[
						'name'     => 'consumer',
						'_alias'   => '',
						'config'   => [
							'propWithDefault'           => 'default value',
							'propWithDefaultOverridden' => 20,
							'propWithoutDefault'        => 'test value',
							'themeName'                 => 'default',
							'themeOptions'              => [ 'default' ],
						],
						'children' => [],
					],
				],
			],
		];

		// Clean up.
		Components\get_registry()->unregister_component( 'provider' );
		Components\get_registry()->unregister_component( 'consumer' );

		$this->assertEquals( $expected, $hydrated, 'Template hydration is not using context.' );
	}

	/**
	 * Tests that context values are passed to non-registered components.
	 */
	public function test_templates_context_without_registration() {
		$this->markTestSkipped( 'Setting context in templates not currently supported.' );

		$template = [
			[
				'name'             => 'provider',
				'config'           => [
					'test_provided' => 20,
				],
				'provides_context' => [
					'test/context' => 'test_provided',
				],
				'children'         => [
					[
						'name'        => 'consumer',
						'use_context' => [
							'test/context' => 'test_used',
						],
					],
				],
			],
		];

		$hydrated = json_decode( wp_json_encode( hydrate_template( $template ) ), true );

		$expected = [
			[
				'name'     => 'provider',
				'_alias'   => '',
				'config'   => [
					'testProvided' => 20,
					'themeName'    => 'default',
					'themeOptions' => [ 'default' ],
				],
				'children' => [
					[
						'name'     => 'consumer',
						'_alias'   => '',
						'config'   => [
							'testUsed'     => 20,
							'themeName'    => 'default',
							'themeOptions' => [ 'default' ],
						],
						'children' => [],
					],
				],
			],
		];

		$this->assertEquals(
			$expected,
			$hydrated,
			'Could not get context in non-registered components.'
		);
	}

	/**
	 * Test template hydration with included template parts.
	 */
	public function test_templates_hydrate_partials() {
		$template = [
			[ 'name' => 'example/component' ],
			[ 'name' => 'template-part/example' ],
		];

		$hydrated = hydrate_template( $template );

		$expected = [
			new Component( 'example/component' ),
			new Component( 'example/component1' ),
			new Component(
				'example/component2',
				[
					'children' => [
							[ 'name' => 'example/component3' ],
					],
				]
			),
		];

		$this->assertEquals( $expected, $hydrated, 'Template partial not hydrated correctly.' );
	}
}
