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
		add_filter( 'upload_dir', [ $this, 'upload_dir_no_subdir' ] );
	}

	/**
	 * Test output for core components.
	 *
	 * @param array     $expected     Shape of the expected component after hydration.
	 * @param Component $component    Component being tested.
	 * @param string    $message      Optional. Message for failure.
	 *
	 * @see \PHPUnit\Framework\Assert::assertEquals
	 */
	public function assertComponentEquals( array $expected, Component $component, string $message = '' ) {

		return $this->assertEquals(
			$expected,
			// Test in array syntax so it's easier to read diffs.
			$component->to_array(),
			$message
		);
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
	 * Test irving/archive-title component.
	 *
	 * @group core-components
	 */
	public function test_component_archive_title() {
		$this->go_to( '?cat=1' );

		$expected = [
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

		$component = new Component( 'irving/archive-title' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-byline component.
	 *
	 * @group core-components
	 */
	public function test_component_post_byline() {
		$this->go_to( '?p=' . $this->get_post_id() );

		$expected = [
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

		$component = new Component( 'irving/post-byline' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-content component.
	 *
	 * @group core-components
	 */
	public function test_component_post_content() {
		$this->go_to( '?p=' . $this->get_post_id() );

		$expected = [
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

		$component = new Component( 'irving/post-content' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-excerpt component.
	 *
	 * @group core-components
	 */
	public function test_component_post_excerpt() {
		$this->go_to( '?p=' . $this->get_post_id() );

		$expected = [
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

		$component = new Component( 'irving/post-excerpt' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-featured-image component.
	 *
	 * @group core-components
	 */
	public function test_component_post_featured_image() {
		$this->go_to( '?p=' . $this->get_post_id() );

		$expected = [
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

		$component = new Component( 'irving/post-featured-image' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-featured-media component.
	 *
	 * @group core-components
	 */
	public function test_component_post_featured_media() {
		$this->go_to( '?p=' . $this->get_post_id() );

		$expected = [
			'name'     => 'irving/post-featured-media',
			'_alias'   => 'irving/fragment',
			'config'   => (object) [
				'postId'       => $this->get_post_id(),
				'aspectRatio'  => '',
				'objectFit'    => 'cover',
				'style'        => [],
				'themeName'    => 'default',
				'themeOptions' => [ 'default' ],
			],
			'children' => [
				[
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
				],
			],
		];

		$component = new Component( 'irving/post-featured-media' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-list component.
	 *
	 * @group core-components
	 */
	public function test_component_post_list() {
		// Demo query args.
		$query_args = [
			'post__in' => [ $this->get_post_id() ],
		];

		// Demo templates.
		$templates = [
			'before'  => [
				[ 'name' => 'example/before' ],
			],
			'after'   => [
				[ 'name' => 'example/after' ],
			],
			'wrapper' => [ 'name' => 'example/wrapper' ],
			'item'    => [ 'name' => 'example/item' ],
		];

		$expected = $this->get_expected_component(
			'irving/post-list',
			[
				'_alias'   => 'irving/fragment',
				'children' => [
					$this->get_expected_component( 'example/before' ),
					$this->get_expected_component(
						'example/wrapper',
						[
							'children' => [
								$this->get_expected_component(
									'irving/post-provider',
									[
										'_alias'   => 'irving/fragment',
										'config'   => [
											'postId' => $this->get_post_id(),
										],
										'children' => [
											$this->get_expected_component( 'example/item' ),
										],
									]
								),
							],
						],
					),
					$this->get_expected_component( 'example/after' ),
				],
			]
		);

		$component = new Component(
			'irving/post-list',
			[
				'config' => [
					'query_args' => $query_args,
					'templates'  => $templates,
				],
			]
		);

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-list component without data.
	 *
	 * @group core-components
	 */
	public function test_component_post_list_no_results() {
		$expected = $this->get_expected_component(
			'irving/post-list',
			[
				'_alias'   => 'irving/fragment',
				'children' => [
					'No results found.',
				],
			]
		);

		$component = new Component(
			'irving/post-list',
			[
				'children' => [ 'No results found.' ],
			]
		);

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-list component without data.
	 *
	 * @group core-components
	 */
	public function test_component_post_list_no_results_template() {
		$expected = $this->get_expected_component(
			'irving/post-list',
			[
				'_alias'   => 'irving/fragment',
				'children' => [
					$this->get_expected_component( 'example/none' ),
				],
			]
		);

		$component = new Component(
			'irving/post-list',
			[
				'config'   => [
					'templates' => [
						'no_results' => [
							[ 'name' => 'example/none' ],
						],
					],
				],
				'children' => [ 'No results found.' ],
			]
		);

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-permalink component.
	 *
	 * @group core-components
	 */
	public function test_component_post_permalink() {
		$this->go_to( '?p=' . $this->get_post_id() );

		$expected = $this->get_expected_component(
			'irving/post-permalink',
			[
				'_alias' => 'irving/link',
				'config' => [
					'href'   => get_the_permalink( $this->get_post_id() ),
					'rel'    => '',
					'style'  => [],
					'target' => '',
					'postId' => $this->get_post_id(),
				],
			]
		);

		$component = new Component( 'irving/post-permalink' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Helper for creating expected output for components.
	 *
	 * Returns default values so you don't have to write as much boilerplate.
	 *
	 * @param string $name Component name.
	 * @param array  $args Optional. Component args.
	 * @return array Expected component array output including defaults.
	 */
	public function get_expected_component( string $name, array $args = [] ): array {

		$args['name'] = $name;

		$component = wp_parse_args(
			$args,
			[
				'name'     => '',
				'_alias'   => '',
				'config'   => [],
				'children' => [],
			],
		);

		$component['config'] = (object) wp_parse_args(
			$args['config'] ?? null,
			[
				'themeName'    => 'default',
				'themeOptions' => [ 'default' ],
			]
		);

		return $component;
	}
}
