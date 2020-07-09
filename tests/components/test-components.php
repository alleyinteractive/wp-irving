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
	 * Test attachment
	 *
	 * @var int attachment ID.
	 */
	public static $attachment_id;


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
	 * Helper to get the attachment ID.
	 *
	 * @return int attachment ID.
	 */
	public function get_attachment_id() {
		return self::$attachment_id;
	}

	/**
	 * Helper to get the attachment URL.
	 *
	 * @return string attachment URL.
	 */
	public function get_attachment_url() {
		$uploads_dir = wp_get_upload_dir();

		return trailingslashit( $uploads_dir['url'] ) . 'test-image.jpg';
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
	 * Helper to get the post content rendered.
	 *
	 * @param int $post_id Post ID.
	 * @return string HTML output of post content.
	 */
	public function get_post_content( int $post_id = 0 ): string {
		if ( empty( $post_id ) ) {
			$post_id = $this->get_post_id();
		}

		$content = get_the_content( null, null, $post_id );

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		return $content;
	}

	/**
	 * Helper to get the post excerpt rendered.
	 *
	 * @param int $post_id Post ID.
	 * @return string HTML output of post content.
	 */
	public function get_post_excerpt( int $post_id = 0 ): string {
		if ( empty( $post_id ) ) {
			$post_id = $this->get_post_id();
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return apply_filters( 'the_excerpt', get_the_excerpt( $post_id ) );
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
		// Test image.
		self::$attachment_id = $factory->attachment->create_object(
			'test-image.jpg',
			null,
			[
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
				'post_excerpt'   => 'Test caption text.',
			]
		);

		// Set up alt text and credit content.
		update_post_meta( self::$attachment_id, '_wp_attachment_image_alt', 'Test alt text.' );
		update_post_meta( self::$attachment_id, 'credit', 'Test credit text.' );

		// Test author.
		self::$author_id = $factory->user->create();

		// Test post.
		self::$post_id = $factory->post->create(
			[
				'post_author'   => self::$author_id,
				'_thumbnail_id' => self::$attachment_id,
			]
		);

	}

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setup();

		global $wp_irving_context;

		// Ensure we get a fresh context store for each test.
		$wp_irving_context = null;

		// Disable date organized uploads.
		add_filter( 'upload_dir', 'upload_dir_no_subdir' );
	}

	/**
	 * Helper used with the `upload_dir` filter to remove the /year/month sub directories from the uploads path and URL.
	 *
	 * Taken from the WP PHPUnit test helpers.
	 *
	 * @param array $uploads The uploads path data.
	 * @return array The altered array.
	 */
	public function upload_dir_no_subdir( $uploads ) {
		$subdir = $uploads['subdir'];

		$uploads['subdir'] = '';
		$uploads['path']   = str_replace( $subdir, '', $uploads['path'] );
		$uploads['url']    = str_replace( $subdir, '', $uploads['url'] );

		return $uploads;
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
						'_alias'   => 'irving/text',
						'config'   => (object) [
							// The format of the archive title changed in WP version 5.5.
							'content'      => version_compare( get_bloginfo( 'version' ), '5.4.99', '>' ) ? 'Category: <span>Uncategorized</span>' : 'Category: Uncategorized',
							'html'         => true,
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
						'_alias'   => 'irving/byline',
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
			[
				'irving/post-content',
				function () {
					return [
						'name'     => 'irving/post-content',
						'_alias'   => 'irving/text',
						'config'   => (object) [
							// The format of the archive title changed in WP version 5.5.
							'content'      => $this->get_post_content(),
							'html'         => true,
							'oembed'       => true,
							'postId'       => $this->get_post_id(),
							'themeName'    => 'default',
							'themeOptions' => [ 'default' ],
						],
						'children' => [],
					];
				},
				function () {
					$this->go_to( '?p=' . $this->get_post_id() );
				},
			],
			[
				'irving/post-excerpt',
				function () {
					return [
						'name'     => 'irving/post-excerpt',
						'_alias'   => 'irving/text',
						'config'   => (object) [
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
							'content'      => $this->get_post_excerpt(),
							'html'         => true,
							'postId'       => $this->get_post_id(),
							'themeName'    => 'default',
							'themeOptions' => [ 'default' ],
						],
						'children' => [],
					];
				},
				function () {
					$this->go_to( '?p=' . $this->get_post_id() );
				},
			],
			[
				'irving/post-featured-image',
				function () {
					return [
						'name'     => 'irving/post-featured-image',
						'_alias'   => 'irving/image',
						'config'   => (object) [
							'alt'          => 'Test alt text.',
							'caption'      => 'Test caption text.',
							'credit'       => 'Test credit text.',
							'src'          => $this->get_attachment_url(),
							'postId'       => $this->get_post_id(),
							'themeName'    => 'default',
							'themeOptions' => [ 'default' ],
						],
						'children' => [],
					];
				},
				function () {
					$this->go_to( '?p=' . $this->get_post_id() );
				},
			],
		];
	}
}
