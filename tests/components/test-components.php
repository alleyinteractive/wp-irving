<?php
/**
 * Class Test_Components.
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use WP_UnitTest_Factory;
use WP_UnitTestCase;
use WP_Query;

/**
 * Tests for WP_Irving\Components functions.
 *
 * @group components
 */
class Test_Components extends WP_UnitTestCase {

	/**
	 * Test author
	 *
	 * @var int Author ID.
	 */
	public static $author_id;

	/**
	 * Test post
	 *
	 * @var int Post ID.
	 */
	public static $post_id;

	/**
	 * Helper to get the author ID.
	 *
	 * @return int Author ID.
	 */
	public function get_author_id() {
		return self::$author_id;
	}

	/**
	 * Helper to get the post ID.
	 *
	 * @return int Post ID.
	 */
	public function get_post_id() {
		return self::$post_id;
	}

	/**
	 * Helper to get the post object
	 *
	 * @return WP_Post Post object.
	 */
	public function get_post() {
		return get_post( self::$post_id );
	}

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetupBeforeClass( $factory ) {
		self::$author_id = $factory->user->create();
		self::$post_id   = $factory->post->create( [ 'post_author' => self::$author_id ] );
	}

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
	 * @param callable $expected Callback to set the expected component after hydration.
	 * @param callable $setup    Optional. A callback function used to set up state.
	 */
	public function test_core_components( string $name, callable $expected, callable $setup = null ) {
		// Run setup logic needed before creating the component.
		if ( ! empty( $setup ) ) {
			call_user_func( $setup );
		}

		$expected  = call_user_func( $expected );
		$component = new Component( $name );

		$this->assertEquals(
			$expected,
			// Test in array syntax so it's easier to read diffs.
			$component->to_array(),
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
				function () {
					return [
						'name'     => 'irving/archive-title',
						'_alias'   => '',
						'config'   => (object) [
							// The format of the archive title changed in WP version 5.5.
							'content'      => version_compare( get_bloginfo( 'version' ), '5.5', '>=' ) ? 'Category: <span>Uncategorized</span>' : 'Category: Uncategorized',
							'themeName'    => 'default',
							'themeOptions' => [ 'default' ],
						],
						'children' => [],
					];
				},
				function () {
					$this->go_to( '?cat=1' );
				},
			],
			[
				'irving/post-byline',
				function () {
					return [
						'name'     => 'irving/post-byline',
						'_alias'   => '',
						'config'   => (object) [
							'singleDelimiter' => ' and ',
							'multiDelimiter'  => ', ',
							'lastDelimiter'   => ', and ',
							'preText'         => 'By ',
							'postId'          => $this->get_post_id(),
							'themeName'       => 'default',
							'themeOptions'    => [ 'default' ],
						],
						'children' => [
							[
								'name'     => 'irving/link',
								'_alias'   => '',
								'config'   => (object) [
									'href'         => get_author_posts_url( $this->get_author_id() ),
									'rel'          => '',
									'style'        => [],
									'target'       => '',
									'themeName'    => 'default',
									'themeOptions' => [ 'default' ],
								],
								'children' => [
									[
										'name'     => 'irving/text',
										'_alias'   => '',
										'config'   => (object) [
											'content'      => get_the_author_meta( 'display_name', $this->get_author_id() ),
											'tag'          => 'span',
											'html'         => false,
											'oembed'       => false,
											'style'        => [],
											'themeName'    => 'default',
											'themeOptions' => [
												'default',
												'unstyled',
												'responsiveEmbed',
												'html',
												'caption',
												'h1',
												'h2',
												'h3',
												'h4',
												'h5',
												'h6',
											],
										],
										'children' => [],
									],
								],
							],
						],
					];
				},
				function() {
					$this->go_to( '?p=' . $this->get_post_id() );
				},
			],
		];
	}
}
