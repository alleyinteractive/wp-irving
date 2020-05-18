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

	function setup() {
		parent::setUp();

		// Hook up template path filters.
		add_filter( 'wp_irving_template_path', function () {
			return dirname( __FILE__ ) . '/inc/templates';
		} );

		add_filter( 'wp_irving_template_part_path', function () {
			return dirname( __FILE__ ) . '/inc/template-parts';
		} );
	}

	function get_template_paths() {
		return [
			[
				[ 'defaults' ]
			],
			[
				[ 'index' ]
			],
			[
				[ 'single' ]
			],
		];
	}

	/**
	 * Test that local template paths is working.
	 *
	 * @dataProvider get_template_paths
	 *
	 * @param array $paths
	 */
	function test_template_paths( $paths ) {
		$path = Templates\locate_template( $paths );

		$this->assertTrue( file_exists( $path ) );
	}

	/**
	 * Test that local template partial paths is working.
	 */
	function test_template_partial_paths() {
		$path = Templates\locate_template_part( 'sidebar' );

		$this->assertTrue( file_exists( $path ) );
	}

	/**
	 * Test default template context.
	 *
	 * @group context
	 */
	function test_template_default_context() {
		$post = $this->factory()->post->create();
		$this->go_to( get_the_permalink( $post ) );

		$context = Templates\get_template_context();

		// Test initial context.
		$this->assertEquals( $post, $context->get( 'irving/post' ), 'Default post context not set.' );
	}

	/**
	 * Test template hydration uses context.
	 *
	 * @group context
	 */
	function test_components_use_context() {
		get_registry()->register_component( 'provider', [
			'config'                        => [
				'prop_with_default'            => [
					'type'                        => 'text',
					'default'                     => 'default value'
				],
				'prop_with_default_overridden' => [
					'type'                        => 'number',
					'default'                     => 10
				],
				'prop_without_default'         => [
					'type'                        => 'text',
				],
			],
			'provides_context'              => [
				'test/with_default'            => 'prop_with_default',
				'test/with_default_overridden' => 'prop_with_default_overridden',
				'test/without_default'         => 'prop_without_default',
			]
		] );

		get_registry()->register_component( 'consumer', [
			'config'                        => [
				'prop_with_default'            => [
					'type'                        => 'text',
				],
				'prop_with_default_overridden' => [
					'type'                        => 'number',
				],
				'prop_without_default'         => [
					'type'                        => 'text',
				],
			],
			'use_context'                   => [
				'test/with_default'            => 'prop_with_default',
				'test/with_default_overridden' => 'prop_with_default_overridden',
				'test/without_default'         => 'prop_without_default',
			],
		] );

		$template = [
			[
				'name'                          => 'provider',
				'config'                        => [
					'prop_with_default_overridden' => 20,
					'prop_without_default'         => 'test value',
				],
				'children'                      => [
					[
						'name'                        => 'consumer',
					],
				],
			],
		];

		$hydrated = Templates\hydrate_components( $template );

		$expected = [
			[
				'name'                         => 'provider',
				'config'                       => (object) [
					'propWithDefault'             => 'default value',
					'propWithDefaultOverridden'   => 20,
					'propWithoutDefault'          => 'test value',
					'themeName'                    => 'default',
					'themeOptions'               => [ 'default' ],
				],
				'children'                     => [
					[
						'name'                       => 'consumer',
						'config'                     => (object) [
							'propWithDefault'           => 'default value',
							'propWithDefaultOverridden' => 20,
							'propWithoutDefault'        => 'test value',
							'themeName'                  => 'default',
							'themeOptions'               => [ 'default' ],
						],
						'children'                   => [],
					],
				],
			],
		];

		$this->assertEquals( $expected, $hydrated, 'Template hydration is not using context.' );
	}

	/**
	 * Test template hydration with included template parts.
	 */
	function test_templates_hydrate_partials() {
		$template = [
			[ 'name' => 'example/component' ],
			[ 'name' => 'template-parts/example' ]
		];

		$hydrated = Templates\hydrate_components( $template );

		$expected = [
			[
				'name'                       => 'example/component',
				'config'                     => (object) [
					'themeName'                  => 'default',
					'themeOptions'               => [ 'default' ],
				],
				'children'                   => [],
			],
			[
				'name'                       => 'example/component1',
				'config'                     => (object) [
					'themeName'                  => 'default',
					'themeOptions'               => [ 'default' ],
				],
				'children'                   => [],
			],
			[
				'name'                       => 'example/component2',
				'config'                     => (object) [
					'themeName'                  => 'default',
					'themeOptions'               => [ 'default' ],
				],
				'children'                   => [
					[
						'name'                       => 'example/component3',
						'config'                     => (object) [
							'themeName'                  => 'default',
							'themeOptions'               => [ 'default' ],
						],
						'children'                   => [],
					]
				],
			]
		];

		$this->assertEquals( $expected, $hydrated, 'Template partial not hydrated correctly.' );
	}
}
