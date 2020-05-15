<?php
/**
 * Class Test_Templates
 *
 * @package WP_Irving
 */

use WP_Irving\Templates;

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
}
