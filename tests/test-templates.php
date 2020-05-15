<?php
/**
 * Class Test_Templates
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\Templates;
use WP_UnitTestCase;

use function WP_Irving\Templates\hydrate_components;

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
			'config'                      => [
				'propWithDefault'            => [
					'type'                      => 'text',
					'default'                   => 'default value'
				],
				'propWithDefaultOverridden'  => [
					'type'                      => 'number',
					'default'                   => 10
				],
				'propWithoutDefault'         => [
					'type'                      => 'text',
				],
			],
			'providesContext'             => [
				'test/withDefault'           => 'propWithDefault',
				'test/withDefaultOverridden' => 'propWithDefaultOverridden',
				'test/withoutDefault'        => 'propWithoutDefault',
			]
		] );

		get_registry()->register_component( 'consumer', [
			'config'                      => [
				'propWithDefault'            => [
					'type'                      => 'text',
				],
				'propWithDefaultOverridden'  => [
					'type'                      => 'number',
				],
				'propWithoutDefault'         => [
					'type'                      => 'text',
				],
			],
			'usesContext'                 => [
				'test/withDefault'           => 'propWithDefault',
				'test/withDefaultOverridden' => 'propWithDefaultOverridden',
				'test/withoutDefault'        => 'propWithoutDefault',
			],
		] );

		$template = [
			[
				'name'                       => 'provider',
				'config'                     => [
					'propWithDefaultOverridden' => 20,
					'propWithoutDefault'        => 'test value',
				],
				'children'                   => [
					[
						'name'                     => 'consumer',
					],
				],
			],
		];

		$hydrated = hydrate_components( $template );

		$expected = [
			[
				'name'                         => 'provider',
				'config'                       => [
					'propWithDefault'             => 'default value',
					'propWithDefaultOverridden'   => 20,
					'propWithoutDefault'          => 'test value',
				],
				'children'                     => [
					[
						'name'                       => 'consumer',
						'config'                     => [
							'propWithDefault'           => 'default value',
							'propWithDefaultOverridden' => 20,
							'propWithoutDefault'        => 'test value',
						],
					],
				],
			],
		];

		$this->assertEquals( $expected, $hydrated, 'Template hydration is not using context.' );
	}
}
