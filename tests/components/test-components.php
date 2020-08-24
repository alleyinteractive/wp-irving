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
	 * Test term.
	 *
	 * @var int Term ID.
	 */
	public static $term_id;

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
	 * Helper to get the term ID.
	 *
	 * @return int Term ID.
	 */
	public function get_term_id() {
		return self::$term_id;
	}

	/**
	 * Helper to get the term object
	 *
	 * @return WP_Term Term object.
	 */
	public function get_term() {
		return get_term( self::$term_id );
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
				'post_title'    => "It's like this & that.", // Tests HTML entities.
				'_thumbnail_id' => self::$attachment_id,
			]
		);

		self::$term_id = $factory->term->create(
			[
				'name'     => 'Example Category',
				'taxonomy' => 'category',
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
		add_filter( 'upload_dir', 'WP_Irving\Test_Helpers::upload_dir_no_subdir' );
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
	 * Test default template context.
	 *
	 * @group context
	 */
	public function test_template_default_context() {
		// Override the global post and wp_query objects for this test.
		global $post;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = $this->factory()->post->create_and_get();

		$context = get_context_store();

		// Test initial context.
		$this->assertEquals( $post->ID, $context->get( 'irving/post_id' ), 'Default post ID context not set.' );
		$this->assertNull( $context->get( 'irving/term_id' ), 'Default term ID context not set.' );
		$this->assertEquals( new WP_Query( [] ), $context->get( 'irving/wp_query' ), 'Default wp query context not set.' );
	}

	/**
	 * Test default template context on a category archive.
	 *
	 * @group context
	 */
	public function test_template_default_context_on_category_archive() {
		$this->go_to( '?taxonomy=category&term=' . $this->get_term()->slug );

		$context = get_context_store();

		$this->assertEquals( $this->get_term_id(), $context->get( 'irving/term_id' ), 'Default term ID context not set.' );
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
			'before'        => [
				[ 'name' => 'example/before' ],
			],
			'after'         => [
				[ 'name' => 'example/after' ],
			],
			'wrapper'       => [ 'name' => 'example/wrapper' ],
			'item'          => [ 'name' => 'example/item' ],
			'interstitials' => [
				0 => [
					[ 'name' => 'example/interstitial' ],
				],
			],
		];

		// Demo templates with item and wrapper in array format.
		$templates_alt = [
			'before'  => [
				[ 'name' => 'example/before' ],
			],
			'after'   => [
				[ 'name' => 'example/after' ],
			],
			'wrapper' => [
				[ 'name' => 'example/wrapper' ],
			],
			'item'    => [
				[ 'name' => 'example/item' ],
			],
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
								$this->get_expected_component( 'example/interstitial' ),
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
						]
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

		$component_alt = new Component(
			'irving/post-list',
			[
				'config' => [
					'query_args' => $query_args,
					'templates'  => $templates,
				],
			]
		);

		$this->assertComponentEquals( $expected, $component );
		$this->assertComponentEquals( $expected, $component_alt );
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
	 * Test irving/post-provider component.
	 *
	 * @group core-components
	 */
	public function test_component_post_provider() {
		// Register a component that receives the post_id context.
		register_component(
			'example/use-context',
			[
				'use_context' => [
					'irving/post_id' => 'post_id',
				],
			]
		);

		$expected = $this->get_expected_component(
			'irving/post-provider',
			[
				'_alias'   => 'irving/fragment',
				'config'   => [
					'postId' => $this->get_post_id(),
				],
				'children' => [
					$this->get_expected_component(
						'example/use-context',
						[
							'config' => [ 'postId' => $this->get_post_id() ],
						]
					),
				],
			]
		);

		$component = new Component(
			'irving/post-provider',
			[
				'config'   => [
					'post_id' => $this->get_post_id(),
				],
				'children' => [
					[ 'name' => 'example/use-context' ],
				],
			]
		);

		// Cleanup.
		unregister_component( 'example/use-context' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-permalink component.
	 *
	 * @group core-components
	 */
	public function test_component_post_social_sharing() {
		$this->go_to( '?p=' . $this->get_post_id() );

		$expected = $this->get_expected_component(
			'irving/post-social-sharing',
			[
				'_alias' => 'irving/social-sharing',
				'config' => [
					'description' => $this->get_post_excerpt(),
					'imageUrl'    => $this->get_attachment_url(),
					'platforms'   => [
						'email',
						'facebook',
						'linkedin',
						'pinterest',
						'reddit',
						'twitter',
						'whatsapp',
					],
					'postId'      => $this->get_post_id(),
					'title'       => get_the_title( $this->get_post_id() ),
					'url'         => get_the_permalink( $this->get_post_id() ),
				],
			]
		);

		$component = new Component( 'irving/post-social-sharing' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-timestamp component.
	 *
	 * @group core-components
	 */
	public function test_component_post_timestamp() {
		$this->go_to( '?p=' . $this->get_post_id() );

		$modified_date = get_the_modified_date( '', $this->get_post_id() );
		$post_date     = get_the_date( '', $this->get_post_id() );

		$expected = $this->get_expected_component(
			'irving/post-timestamp',
			[
				'_alias' => 'irving/text',
				'config' => [
					'content'      => $post_date,
					'modifiedDate' => $modified_date,
					'postId'       => $this->get_post_id(),
					'postDate'     => $post_date,
				],
			]
		);

		$component = new Component( 'irving/post-timestamp' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-timestamp component with non-default formats.
	 *
	 * @group core-components
	 */
	public function test_component_post_timestamp_with_formats() {
		$this->go_to( '?p=' . $this->get_post_id() );

		$modified_date = get_the_modified_date( 'l F j, Y', $this->get_post_id() );
		$post_date     = get_the_date( 'Ymd', $this->get_post_id() );

		$expected = $this->get_expected_component(
			'irving/post-timestamp',
			[
				'_alias' => 'irving/text',
				'config' => [
					'content'      => sprintf( 'Posted: %1$s. Modified: %2$s', $post_date, $modified_date ),
					'modifiedDate' => $modified_date,
					'postId'       => $this->get_post_id(),
					'postDate'     => $post_date,
				],
			]
		);

		$component = new Component(
			'irving/post-timestamp',
			[
				'config' => [
					'content_format'       => 'Posted: %1$s. Modified: %2$s',
					'modified_date_format' => 'l F j, Y',
					'post_date_format'     => 'Ymd',
				],
			]
		);

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/post-title component.
	 *
	 * @group core-components
	 */
	public function test_component_post_title() {
		$this->go_to( '?p=' . $this->get_post_id() );

		$expected = $this->get_expected_component(
			'irving/post-title',
			[
				'_alias' => 'irving/text',
				'config' => [
					'content' => 'It’s like this & that.', // Texturized.
					'postId'  => $this->get_post_id(),
				],
			]
		);

		$component = new Component( 'irving/post-title' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/query-pagination component.
	 *
	 * @group core-components
	 */
	public function test_component_query_pagination() {
		// Set up test specific category.
		$cat = wp_insert_category( [ 'cat_name' => 'query-pagination' ] );

		// Set up test posts.
		$this->factory()->post->create_many(
			30,
			// Set up special category for these tests.
			[ 'post_category' => [ $cat ] ]
		);

		$this->go_to( "?cat={$cat}&paged=2" );

		$category = get_category( $cat );

		$expected = $this->get_expected_component(
			'irving/query-pagination',
			[
				'_alias' => 'irving/pagination',
				'config' => [
					'currentPage'         => 2,
					'totalPages'          => 3,
					'baseUrl'             => "/category/{$category->slug}/",
					'displayFirstAndLast' => true,
					'displayPrevAndNext'  => true,
					'paginationFormat'    => 'page/%1$d/',
					'range'               => 5,
				],
			]
		);

		$component = new Component( 'irving/query-pagination' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/query-search-form component.
	 *
	 * @group core-components
	 */
	public function test_component_query_search_form() {
		$this->go_to( '?s=Irving' );

		$expected = $this->get_expected_component(
			'irving/query-search-form',
			[
				'_alias' => 'irving/search-form',
				'config' => [
					'baseUrl'            => '/',
					'searchTerm'         => 'Irving',
					'searchTermQueryArg' => 's',
					'style'              => [],
				],
			]
		);

		$component = new Component( 'irving/query-search-form' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/query-search-form component.
	 *
	 * @group core-components
	 */
	public function test_component_query_search_results_found() {
		// Create a unique test post to show up in search.
		$this->factory->post->create( [ 'post_title' => 'Irving Search' ] );

		$this->go_to( '?s=Irving%20Search' );

		$expected = $this->get_expected_component(
			'irving/query-search-results-found',
			[
				'_alias' => 'irving/text',
				'config' => [
					'content'       => '1 results found for "Irving Search"',
					'contentFormat' => '%1$s results found for "%2$s"',
				],
			]
		);

		$component = new Component( 'irving/query-search-results-found' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/query-search-form component with a custom format.
	 *
	 * @group core-components
	 */
	public function test_component_query_search_results_found_content_format() {
		// Create several test posts to show up in search.
		$this->factory->post->create_many( 15, [ 'post_title' => 'Irving Search' ] );

		$this->go_to( '?s=Irving%20Search' );

		$expected = $this->get_expected_component(
			'irving/query-search-results-found',
			[
				'_alias' => 'irving/text',
				'config' => [
					'content'       => 'Found 15 results for "Irving Search" on page 1 of 2',
					'contentFormat' => 'Found %1$s results for "%2$s" on page %3$s of %4$s',
				],
			]
		);

		$component = new Component(
			'irving/query-search-results-found',
			[
				'config' => [
					'content_format' => 'Found %1$s results for "%2$s" on page %3$s of %4$s',
				],
			],
		);

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/site-info component.
	 *
	 * @group core-components
	 */
	public function test_component_site_info() {

		$show_types = [
			'',
			'name',
			'description',
			'wpurl',
			'url',
			'admin_email',
			'charset',
			'version',
			'html_type',
			'language',
			'stylesheet_url',
			'stylesheet_directory',
			'template_url',
			'template_directory',
			'pingback_url',
			'atom_url',
			'rdf_url',
			'rss_url',
			'rss2_url',
			'comments_atom_url',
			'comments_rss2_url',
		];

		foreach ( $show_types as $show ) {
			$expected = $this->get_expected_component(
				'irving/site-info',
				[
					'_alias' => 'irving/text',
					'config' => [
						'content' => get_bloginfo( $show ),
						'oembed'  => false,
						'show'    => $show,
						'style'   => [],
						'tag'     => 'span',
					],
				]
			);

			$component = new Component(
				'irving/site-info',
				[
					'config' => [
						'show' => $show,
					],
				],
			);

			$this->assertComponentEquals( $expected, $component, "Failed with show value of {$show}." );
		}
	}

	/**
	 * Test irving/site-logo component.
	 *
	 * @group core-components
	 */
	public function test_component_site_logo() {
		// Set our test image as the site logo.
		set_theme_mod( 'custom_logo', $this->get_attachment_id() );

		$expected = $this->get_expected_component(
			'irving/site-logo',
			[
				'_alias' => 'irving/logo',
				'config' => [
					'href'         => '/',
					'logoImageUrl' => $this->get_attachment_url(),
					'siteName'     => get_bloginfo( 'name' ),
				],
			]
		);

		$component = new Component( 'irving/site-logo' );

		$this->assertComponentEquals( $expected, $component );

	}

	/**
	 * Test irving/site-menu component.
	 *
	 * @group core-components
	 */
	public function test_component_site_menu() {
		// Create some test pages.
		$posts = $this->factory->post->create_many( 4, [ 'post_type' => 'page' ] );

		// Create a test menu.
		$menu_id = wp_create_nav_menu( 'test-menu' );

		$menu_items = [];

		// Set up menu items.
		foreach ( $posts as $key => $post ) {
			$menu_args = [
				'menu-item-type'      => 'post_type',
				'menu-item-object'    => 'page',
				'menu-item-status'    => 'publish',
				'menu-item-title'     => "Post {$post}",
				'menu-item-object-id' => $post,
				// Menus above the second in the array children of the previous item.
				'menu-item-parent-id' => ( $key > 1 ) ? $menu_items[ $key - 1 ] : 0,
			];

			$menu_items[] = wp_update_nav_menu_item( $menu_id, 0, $menu_args );
		}

		// Set the test menu to our test location.
		set_theme_mod(
			'nav_menu_locations',
			[
				'test-location' => $menu_id,
			]
		);

		$expected = $this->get_expected_component(
			'irving/site-menu',
			[
				'_alias'   => 'irving/menu',
				'config'   => [
					'displayName'  => false,
					'location'     => 'test-location',
					'menuId'       => $menu_id,
					'menuName'     => 'test-menu',
					'themeOptions' => [
						'default',
						'defaultVertical',
					],
				],
				'children' => [
					$this->get_expected_component(
						'irving/menu-item',
						[
							'config' => [
								'attributeTitle' => '',
								'classes'        => [],
								'id'             => $menu_items[0],
								'parentId'       => 0,
								'target'         => '',
								'title'          => get_the_title( $menu_items[0] ),
								'url'            => get_the_permalink( $posts[0] ),
							],
						]
					),
					$this->get_expected_component(
						'irving/menu-item',
						[
							'config'   => [
								'attributeTitle' => '',
								'classes'        => [],
								'id'             => $menu_items[1],
								'parentId'       => 0,
								'target'         => '',
								'title'          => get_the_title( $menu_items[1] ),
								'url'            => get_the_permalink( $posts[1] ),
							],
							'children' => [
								$this->get_expected_component(
									'irving/menu-item',
									[
										'config'   => [
											'attributeTitle' => '',
											'classes'  => [],
											'id'       => $menu_items[2],
											'parentId' => $menu_items[1],
											'target'   => '',
											'title'    => get_the_title( $menu_items[2] ),
											'url'      => get_the_permalink( $posts[2] ),
										],
										'children' => [
											$this->get_expected_component(
												'irving/menu-item',
												[
													'config' => [
														'attributeTitle' => '',
														'classes'  => [],
														'id'       => $menu_items[3],
														'parentId' => $menu_items[2],
														'target'   => '',
														'title'    => get_the_title( $menu_items[3] ),
														'url'      => get_the_permalink( $posts[3] ),
													],
												]
											),
										],
									]
								),
							],
						]
					),
				],
			]
		);

		$component = new Component(
			'irving/site-menu',
			[
				'config' => [
					'location' => 'test-location',
				],
			]
		);

		$this->assertComponentEquals( $expected, $component );

	}

	/**
	 * Test irving/term-provider component.
	 *
	 * @group core-components
	 */
	public function test_component_term_provider() {
		// Register a component that receives the term_id context.
		register_component(
			'example/use-context',
			[
				'use_context' => [
					'irving/term_id' => 'term_id',
				],
			]
		);

		$expected = $this->get_expected_component(
			'irving/term-provider',
			[
				'_alias'   => 'irving/fragment',
				'config'   => [
					'termId' => $this->get_term_id(),
				],
				'children' => [
					$this->get_expected_component(
						'example/use-context',
						[
							'config' => [ 'termId' => $this->get_term_id() ],
						]
					),
				],
			]
		);

		$component = new Component(
			'irving/term-provider',
			[
				'config'   => [
					'term_id' => $this->get_term_id(),
				],
				'children' => [
					[ 'name' => 'example/use-context' ],
				],
			]
		);

		// Cleanup.
		unregister_component( 'example/use-context' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/term-name component.
	 *
	 * @group core-components
	 */
	public function test_component_term_name() {
		$this->go_to( '?taxonomy=category&term=' . $this->get_term()->slug );

		$expected = $this->get_expected_component(
			'irving/term-name',
			[
				'_alias' => 'irving/text',
				'config' => [
					'content' => $this->get_term()->name,
					'termId'  => $this->get_term_id(),
				],
			]
		);

		$component = new Component( 'irving/term-name' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/term-link component.
	 *
	 * @group core-components
	 */
	public function test_component_term_link() {
		$this->go_to( '?taxonomy=category&term=' . $this->get_term()->slug );

		$expected = $this->get_expected_component(
			'irving/term-link',
			[
				'_alias' => 'irving/link',
				'config' => [
					'href'   => get_term_link( (int) $this->get_term_id() ),
					'rel'    => '',
					'style'  => [],
					'target' => '',
					'termId' => $this->get_term_id(),
				],
			]
		);

		$component = new Component( 'irving/term-link' );

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/term-list component.
	 *
	 * @group core-components
	 */
	public function test_component_term_list() {
		// Demo templates.
		$templates = [
			'before'        => [
				[ 'name' => 'example/before' ],
			],
			'after'         => [
				[ 'name' => 'example/after' ],
			],
			'wrapper'       => [ 'name' => 'example/wrapper' ],
			'item'          => [ 'name' => 'example/item' ],
			'interstitials' => [
				0 => [
					[ 'name' => 'example/interstitial' ],
				],
			],
		];

		// Demo templates with item and wrapper in array format.
		$templates_alt = [
			'before'  => [
				[ 'name' => 'example/before' ],
			],
			'after'   => [
				[ 'name' => 'example/after' ],
			],
			'wrapper' => [
				[ 'name' => 'example/wrapper' ],
			],
			'item'    => [
				[ 'name' => 'example/item' ],
			],
		];

		$categories = wp_get_post_categories( $this->get_post_id() );

		$expected = $this->get_expected_component(
			'irving/term-list',
			[
				'_alias'   => 'irving/fragment',
				'config'   => [
					'objectIds' => [ $this->get_post_id() ],
				],
				'children' => [
					$this->get_expected_component( 'example/before' ),
					$this->get_expected_component(
						'example/wrapper',
						[
							'children' => [
								$this->get_expected_component( 'example/interstitial' ),
								$this->get_expected_component(
									'irving/term-provider',
									[
										'_alias'   => 'irving/fragment',
										'config'   => [
											'termId' => $categories[0],
										],
										'children' => [
											$this->get_expected_component( 'example/item' ),
										],
									]
								),
							],
						]
					),
					$this->get_expected_component( 'example/after' ),
				],
			]
		);

		$component = new Component(
			'irving/term-list',
			[
				'config' => [
					'object_ids' => [ $this->get_post_id() ],
					'query_args' => [
						'taxonomy' => 'category',
					],
					'templates'  => $templates,
				],
			]
		);

		$component_alt = new Component(
			'irving/term-list',
			[
				'config' => [
					'object_ids' => [ $this->get_post_id() ],
					'query_args' => [
						'taxonomy' => 'category',
					],
					'templates'  => $templates,
				],
			]
		);

		$this->assertComponentEquals( $expected, $component );
		$this->assertComponentEquals( $expected, $component_alt );
	}

	/**
	 * Test irving/video component.
	 *
	 * @group core-components
	 * @group video
	 */
	public function test_component_video() {
		$expected = $this->get_expected_component(
			'irving/video',
			[
				'_alias' => 'irving/text',
				'config' => [
					'aspectRatio' => '16:9',
					'videoUrl'    => 'https://www.youtube.com/watch?v=ITt0uThK1G0',
					'content'     => wp_oembed_get( 'https://www.youtube.com/watch?v=ITt0uThK1G0' ),
					'oembed'      => true,
					'tag'         => 'div',
					'style'       => [
						'paddingBottom' => '56.25%',
					],
				],
			]
		);

		$component = new Component(
			'irving/video',
			[
				'config' => [
					'video_url' => 'https://www.youtube.com/watch?v=ITt0uThK1G0',
				],
			]
		);

		$this->assertComponentEquals( $expected, $component );
	}

	/**
	 * Test irving/coral-comment-counts component.
	 *
	 * @group core-components
	 * @group coral
	 */
	public function test_component_coral_comment_counts() {
		// Mock integration to Coral.
		update_option( 'irving_integrations', [ 'coral_url' => 'https://example.coral.test' ] );

		$expected = $this->get_expected_component(
			'irving/coral-comment-count',
			[
				'config' => [
					'articleUrl' => get_the_permalink( self::$post_id ),
					'embedUrl'   => 'https://example.coral.test',
					'noText'     => false,
				],
			]
		);

		$component = new Component(
			'irving/coral-comment-count',
			[
				'config' => [
					'post_id' => self::$post_id,
				],
			]
		);

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
			]
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
