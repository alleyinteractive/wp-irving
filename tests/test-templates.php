<?php
/**
 * Class Test_Templates
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\Templates;
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
				return dirname( __FILE__ ) . '/inc/templates';
			}
		);

		add_filter(
			'wp_irving_template_part_path',
			function () {
				return dirname( __FILE__ ) . '/inc/template-parts';
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
		$path = Templates\locate_template( $paths );

		$this->assertTrue( file_exists( $path ) );
	}

	/**
	 * Test that local template partial paths is working.
	 */
	public function test_template_partial_paths() {
		$path = Templates\locate_template_part( 'sidebar' );

		$this->assertTrue( file_exists( $path ) );
	}

	/**
	 * Test default template context.
	 *
	 * @group context
	 */
	public function test_template_default_context() {
		// Override the global post object for this test.
		global $post;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = $this->factory()->post->create_and_get();

		$context = Templates\get_template_context();

		// Test initial context.
		$this->assertEquals( $post->ID, $context->get( 'irving/post' ), 'Default post context not set.' );
	}

	/**
	 * Test template hydration uses context.
	 *
	 * @group context
	 */
	public function test_components_use_context() {
		get_registry()->register_component(
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

		get_registry()->register_component(
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

		$hydrated = Templates\hydrate_components( $template );

		$expected = [
			[
				'name'     => 'provider',
				'config'   => (object) [
					'propWithDefault'           => 'default value',
					'propWithDefaultOverridden' => 20,
					'propWithoutDefault'        => 'test value',
					'themeName'                 => 'default',
					'themeOptions'              => [ 'default' ],
				],
				'children' => [
					[
						'name'     => 'consumer',
						'config'   => (object) [
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
		get_registry()->unregister_component( 'provider' );
		get_registry()->unregister_component( 'consumer' );

		$this->assertEquals( $expected, $hydrated, 'Template hydration is not using context.' );
	}

	/**
	 * Tests that context values are passed to non-registered components.
	 */
	public function test_templates_context_without_registration() {
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

		$expected = [
			[
				'name'     => 'provider',
				'config'   => (object) [
					'testProvided' => 20,
					'themeName'    => 'default',
					'themeOptions' => [ 'default' ],
				],
				'children' => [
					[
						'name'     => 'consumer',
						'config'   => (object) [
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
			Templates\hydrate_components( $template ),
			'Could not get context in non-registered components.'
		);
	}

	/**
	 * Test template hydration with included template parts.
	 */
	public function test_templates_hydrate_partials() {
		$template = [
			[ 'name' => 'example/component' ],
			[ 'name' => 'template-parts/example' ],
		];

		$hydrated = Templates\hydrate_components( $template );

		$expected = [
			[
				'name'     => 'example/component',
				'config'   => (object) [
					'themeName'    => 'default',
					'themeOptions' => [ 'default' ],
				],
				'children' => [],
			],
			[
				'name'     => 'example/component1',
				'config'   => (object) [
					'themeName'    => 'default',
					'themeOptions' => [ 'default' ],
				],
				'children' => [],
			],
			[
				'name'     => 'example/component2',
				'config'   => (object) [
					'themeName'    => 'default',
					'themeOptions' => [ 'default' ],
				],
				'children' => [
					[
						'name'     => 'example/component3',
						'config'   => (object) [
							'themeName'    => 'default',
							'themeOptions' => [ 'default' ],
						],
						'children' => [],
					],
				],
			],
		];

		$this->assertEquals( $expected, $hydrated, 'Template partial not hydrated correctly.' );
	}

	/**
	 * Tests that context values are passed to non-registered components.
	 */
	function test_templates_with_text_nodes() {

		$template = [
			[
				'name'   => 'irving/text',
				'config' => [
					'content' => 'Foo Bar',
				],
			],
			[ 'name' => 'example/component' ]
		];

		$expected = [
			'Foo Bar',
			[
				'name'     => 'example/component',
				'config'   => (object) [
					'themeName'    => 'default',
					'themeOptions' => [ 'default' ],
				],
				'children' => [],
			],
		];

		$this->assertEquals(
			$expected,
			Templates\hydrate_components( $template ),
			'Could not get context in non-registered components.'
		);
	}
}
