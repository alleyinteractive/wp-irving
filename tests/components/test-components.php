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
 * Tests for the component class.
 *
 * @group components
 */
class Test_Components extends WP_UnitTestCase {

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
}
