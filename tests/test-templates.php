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
	 * Test context.
	 */
	function test_template_context() {
		$context = Templates\get_template_context();

		// Test initial context.
		$this->assertEquals( get_the_ID(), $context->get( 'irving/post' ), 'Default post context unset.' );

		// Mock context values.
		$steps = [ 10, 20, 30 ];

		// Set context.
		foreach ( $steps as $post_id ) {
			$context->set( 'irving/post', $post_id );
			$this->assertEquals( $post_id, $context->get( 'irving/post' ), 'Could not confirm context was updated.' );
		}

		// Remove the last step before resetting.
		array_pop( $steps );

		// Reverse through the array.
		while ( ! empty( $steps ) ) {
			$value = array_pop( $steps );
			$context->reset();
			$this->assertEquals( $value, $context->get( 'irving/post' ), 'Could not reset context.' );
		}

		// Check default context.
		$context->reset();
		$this->assertEquals( get_the_ID(), $context->get( 'irving/post' ), 'Could not reset default context.' );
	}
}
