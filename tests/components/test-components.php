<?php
/**
 * Class Test_Components.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_UnitTestCase;
use WP_Query;

/**
 * Tests for WP_Irving\Components functions.
 *
 * @group components
 */
class Test_Components extends WP_UnitTestCase {

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setup();

		global $wp_irving_context;

		// Ensure we get a fresh context store for each test.
		$wp_irving_context = null;
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

		$context = get_context_store();

		// Test initial context.
		$this->assertEquals( $post->ID, $context->get( 'irving/post_id' ), 'Default post ID context not set.' );
		$this->assertEquals( new WP_Query( [] ), $context->get( 'irving/wp_query' ), 'Default wp query context not set.' );
	}

	/**
	 * Test output for core components.
	 *
	 * @dataProvider get_core_component_data
	 * @group core-components
	 *
	 * @param string   $name     Name of the component being tested.
	 * @param array    $expected Expected component after hydration.
	 * @param callable $setup    Optional. A callback function used to set up state.
	 */
	public function test_core_components( string $name, array $expected, $setup = null ) {
		// Run setup logic needed before creating the component.
		if ( ! empty( $setup ) ) {
			call_user_func( $setup );
		}

		$component = new Component( $name );

		$this->assertSame(
			wp_json_encode( $expected ),
			wp_json_encode( $component ),
			sprintf( 'Broken component: %s', esc_html( $name ) )
		);
	}

	/**
	 * Data provider for test_core_components().
	 *
	 * @return array Test args
	 */
	public function get_core_component_data() {
		return [
			[
				'irving/archive-title',
				[
					'name'     => 'irving/archive-title',
					'config'   => [
						'content'      => 'Category: Uncategorized',
						'themeName'    => 'default',
						'themeOptions' => [ 'default' ],
					],
					'children' => [],
				],
				function() {
					$this->go_to( '?cat=1' );
				},
			],
		];
	}
}
