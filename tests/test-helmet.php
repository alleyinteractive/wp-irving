<?php
/**
 * Class Test_Helmet
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\Templates;
use function WP_Irving\Templates\create_or_update_title;
use function WP_Irving\Templates\get_title_from_helmet;
use function WP_Irving\Templates\inject_head_tags;
use function WP_Irving\Templates\parse_html;
use WP_UnitTestCase;

/**
 * Test helmet functionality.
 *
 * @group templates
 */
class Test_Helmet extends WP_UnitTestCase {

	/**
	 * Example output from Yoast's `wpseo_head` action.
	 *
	 * @var string
	 */
	public $example_markup = '<!-- This site is optimized with the Yoast SEO plugin v14.1 - https://yoast.com/wordpress/plugins/seo/ -->
<title>Irving Development - Just another WordPress site</title>
<meta name="description" content="Just another WordPress site" />
<meta name="robots" content="index, follow" />
<link rel="canonical" href="https://irving.alley.test/" />
<meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
<meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
<script type="application/ld+json" class="yoast-schema-graph">{"@context":"https://schema.org","@graph":[{"@type":"WebSite","@id":"https://irving.alley.test/#website","url":"https://irving.alley.test/","name":"Irving Development","description":"Just another WordPress site","potentialAction":[{"@type":"SearchAction","target":"https://irving.alley.test/?s={search_term_string}","query-input":"required name=search_term_string"}],"inLanguage":"en-US"},{"@type":"CollectionPage","@id":"https://irving.alley.test/#webpage","url":"https://irving.alley.test/","name":"Irving Development - Just another WordPress site","isPartOf":{"@id":"https://irving.alley.test/#website"},"description":"Just another WordPress site","inLanguage":"en-US"}]}</script>
<!-- / Yoast SEO plugin. -->';

	/**
	 * Get a helmet component instance for testing.
	 *
	 * @param string|null $title Optional. Creates a child title component if
	 *                           the value is a string.
	 * @return Component
	 */
	public function get_helmet_component( ?string $title = null ): Component {

		// New helmet component.
		$helmet = new Component( 'irving/helmet' );

		// No title component.
		if ( is_null( $title ) ) {
			return $helmet;
		}

		return $helmet->set_child( $this->get_title_component( $title ) );
	}

	/**
	 * Get a title component instance for testing.
	 *
	 * @param string $title Title value.
	 * @return Component
	 */
	public function get_title_component( string $title = '' ): Component {
		return ( new Component( 'title' ) )->set_child( $title );
	}

	/**
	 * Return an array that matches the schema for the Components API.
	 *
	 * @return array
	 */
	public function get_blank_data_array(): array {
		return [
			'defaults'       => [],
			'page'           => [],
			'providers'      => [],
			'redirectTo'     => '',
			'redirectStatus' => 0,
		];
	}

	/**
	 * Return the result from a clean run of `setup_helmet()`.
	 *
	 * @return array
	 */
	public function get_helmet_setup_result(): array {
		return Templates\setup_helmet(
			$this->get_blank_data_array(),
			new \WP_Query(),
			'site',
			'/',
			new \WP_Rest_Request()
		);
	}

	/**
	 * Get an example meta component.
	 *
	 * @return Component
	 */
	public function get_example_meta_component(): Component {
		return ( new Component( 'meta' ) )
			->set_config( 'name', 'robots' )
			->set_config( 'content', 'noindex, follow' );
	}

	/**
	 * Test the `get_helmet_component()` helper.
	 */
	public function test_get_helmet_component() {

		// No parameter will get a simple component.
		$this->assertEquals( ( new Component( 'irving/helmet' ) ), $this->get_helmet_component() );

		// Empty title.
		$this->assertEquals(
			( new Component( 'irving/helmet' ) )
				->set_child(
					( new Component( 'title' ) )
						->set_child( '' )
				),
			$this->get_helmet_component( '' )
		);

		// Example title.
		$this->assertEquals(
			( new Component( 'irving/helmet' ) )
				->set_child(
					( new Component( 'title' ) )
						->set_child( 'Foo Bar' )
				),
			$this->get_helmet_component( 'Foo Bar' )
		);
	}

	/**
	 * Tests the automatic insertion and filters for <Helmet>.
	 */
	function test_setup_helmet() {

		/**
		 * Setup some data to use while testing.
		 */
		$default_title  = ( new Component( 'title' ) )->set_child( get_bloginfo( 'name' ) );
		$default_helmet = ( new Component( 'irving/helmet' ) )->set_child( $default_title );

		$page_title  = ( new Component( 'title' ) )->set_child( wp_title( '&raquo;', false ) );
		$page_helmet = ( new Component( 'irving/helmet' ) )->set_child( $page_title );
	}

	/**
	 * Test the basic functionality of the `setup_helmet()` method.
	 */
	public function test_basic_setup_helmet() {

		// Run `setup_helmet()` on empty/blank data.
		$data_with_helmet = $this->get_helmet_setup_result();

		$this->assertEquals(
			$this->get_helmet_component( get_bloginfo( 'name' ) ),
			$data_with_helmet['defaults'][0],
			'Default Helmet title is not correct'
		);

		$this->assertEquals(
			$this->get_helmet_component( wp_title( '&raquo;', false ) ),
			$data_with_helmet['page'][0],
			'Page Helmet title is not correct'
		);
	}

	/**
	 * Test disabling the `setup_helmet()` functionality via filter.
	 */
	public function test_disabling_helmet_setup() {

		// Disable functionality.
		add_filter( 'wp_irving_setup_helmet', '__return_false' );

		// Run `setup_helmet()` on empty/blank data.
		$data_with_helmet = $this->get_helmet_setup_result();

		// Nothing should have happened.
		$this->assertEquals( [], $data_with_helmet['defaults'], 'Defaults array was not empty.' );
		$this->assertEquals( [], $data_with_helmet['page'],  'Page array was not empty.' );

		// Re-enable functionality.
		add_filter( 'wp_irving_setup_helmet', '__return_true' );
	}

	/**
	 * Test the default helmet filter.
	 */
	public function test_default_helmet_filter() {

		// Get an example meta component.
		$meta_example = $this->get_example_meta_component();

		// Filter the default helmet component to include the meta component.
		add_filter(
			'wp_irving_default_helmet_component',
			function( $helmet ) use ( $meta_example ) {
				$helmet->prepend_child( $meta_example );
				return $helmet;
			}
		);

		// Run setup with the new helmet filter in place.
		$data_with_helmet = $this->get_helmet_setup_result();

		$this->assertEquals(
			[
				$meta_example,
				$this->get_title_component( get_bloginfo( 'name' ) ),
			],
			$data_with_helmet['defaults'][0]->get_children(),
			'Default helmet component has incorrect children.'
		);
	}

	/**
	 * Test the page helmet filter.
	 */
	public function test_page_helmet_filter() {

		// Get an example meta component.
		$meta_example = $this->get_example_meta_component();

		// Filter the page helmet component to include the meta component.
		add_filter(
			'wp_irving_page_helmet_component',
			function( $helmet ) use ( $meta_example ) {
				$helmet->prepend_child( $meta_example );
				return $helmet;
			}
		);

		// Run setup with the new helmet filter in place.
		$data_with_helmet = $this->get_helmet_setup_result();

		$this->assertEquals(
			[
				$meta_example,
				$this->get_title_component( wp_title( '&raquo;', false ) ),
			],
			$data_with_helmet['page'][0]->get_children(),
			'Page helmet component has incorrect children.'
		);
	}

	/**
	 * Test the `inject_head_tags` function.
	 */
	public function test_inject_head_tags() {

		// Example components.
		$helmet = new Component( 'irving/helmet' );
		$title  = ( new Component( 'title' ) )->set_child( 'Irving Development - Just another WordPress site' );

		// Parse the example markup, only grabbing the title tag.
		$parsed_html = parse_html( $this->example_markup, [ 'title' ] );

		$this->assertEquals(
			( new Component( 'irving/helmet' ) )->set_child( $title ),
			inject_head_tags( $helmet, $parsed_html )
		);
	}

	/**
	 * Test the `get_title_from_helmet()` function.
	 */
	public function test_get_title_from_helmet() {

		// Helmet has no children.
		$helmet = new Component( 'irving/helmet' );
		$this->assertNull( get_title_from_helmet( $helmet ) );

		// Helmet->Title has no children.
		$helmet->set_child( new Component( 'title' ) );
		$this->assertNull( get_title_from_helmet( $helmet ) );

		// Helmet->Title->first child is a component.
		$helmet->set_child( ( new Component( 'title' ) )->set_child( new Component( 'irving/text' ) ) );
		$this->assertNull( get_title_from_helmet( $helmet ) );

		// Title is `Foo Bar`.
		$helmet->set_child( ( new Component( 'title' ) )->set_child( 'Foo Bar' ) );
		$this->assertEquals( 'Foo Bar', get_title_from_helmet( $helmet ) );
	}


	/**
	 * Helper to get a Helmet component without a title.
	 *
	 * @return Component
	 */
	public function get_no_title() {
		return new Component( 'irving/helmet' );
	}

	/**
	 * Helper to get a Helmet component with a title of `Foo Bar`.
	 *
	 * @return Component
	 */
	public function get_foo_bar_title() {
		return ( new Component( 'irving/helmet' ) )
			->prepend_child(
				new Component(
					'title',
					[
						'children' => [ 'Foo Bar' ]
					]
				)
			);
	}

	/**
	 * Helper to get a Helmet component with a title of `Hello World`.
	 *
	 * @return Component
	 */
	public function get_hello_world_title() {
		return ( new Component( 'irving/helmet' ) )
			->prepend_child(
				new Component(
					'title',
					[
						'children' => [ 'Hello World' ]
					]
				)
			);
	}

	/**
	 * Test the `create_or_update_title` function.
	 */
	public function test_create_or_update_title() {

		// Confirm expected original data.
		$this->assertNull( get_title_from_helmet( $this->get_no_title() ) );
		$this->assertEquals( 'Foo Bar', get_title_from_helmet( $this->get_foo_bar_title() ) );
		$this->assertEquals( 'Hello World', get_title_from_helmet( $this->get_hello_world_title() ) );

		// Test no parameter.
		$this->assertEquals( '', get_title_from_helmet( create_or_update_title( $this->get_no_title() ) ) );  // Create a title.
		$this->assertEquals( '', get_title_from_helmet( create_or_update_title( $this->get_foo_bar_title() ) ) ); // Update a title.

		// Test blank string.
		$this->assertEquals( '', get_title_from_helmet( create_or_update_title( $this->get_no_title(), '' ) ) ); // Create a title.
		$this->assertEquals( '', get_title_from_helmet( create_or_update_title( $this->get_hello_world_title(), '' ) ) ); // Update a title.

		// Test title creation.
		$this->assertEquals( 'Foo Bar', get_title_from_helmet( create_or_update_title( $this->get_no_title(), 'Foo Bar' ) ) );
		$this->assertEquals( 'Hello World', get_title_from_helmet( create_or_update_title( $this->get_no_title(), 'Hello World' ) ) );

		// Test title updating.
		$this->assertEquals( 'Hello World', get_title_from_helmet( create_or_update_title( $this->get_foo_bar_title(), 'Hello World' ) ) );
		$this->assertEquals( 'Foo Bar', get_title_from_helmet( create_or_update_title( $this->get_hello_world_title(), 'Foo Bar' ) ) );
	}

	/**
	 * Test the `parse_html()` function.
	 */
	public function test_parse_html() {

		// Test title, which only has content.
		$this->assertEquals(
			[
				'title' => [
					[
						'attributes' => [],
						'content'    => 'Irving Development - Just another WordPress site',
					]
				],
			],
			parse_html( $this->example_markup, [ 'title' ] )
		);

		// Test meta and links, which only have attributes (but there are many).
		$this->assertEquals(
			[
				'meta' => [
					[
						'attributes' => [
							'name'    => 'description',
							'content' => 'Just another WordPress site',
						],
						'content'    => '',
					],
					[
						'attributes' => [
							'name'    => 'robots',
							'content' => 'index, follow',
						],
						'content'    => '',
					],
					[
						'attributes' => [
							'name' => 'googlebot',
							'content' => 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
						],
						'content'    => '',
					],
					[
						'attributes' => [
							'name' => 'bingbot',
							'content' => 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
						],
						'content'    => '',
					],
				],
				'link' => [
					[
						'attributes' => [
							'rel'  => 'canonical',
							'href' => 'https://irving.alley.test/',
						],
						'content' => '',
					],
				],
			],
			parse_html( $this->example_markup, [ 'meta', 'link' ] )
		);

		// Test script, which contains attributes and content.
		$this->assertEquals(
			[
				'script' => [
					[
						'attributes' => [
							'type'  => 'application/ld+json',
							'class' => 'yoast-schema-graph',
						],
						'content'    => '{"@context":"https://schema.org","@graph":[{"@type":"WebSite","@id":"https://irving.alley.test/#website","url":"https://irving.alley.test/","name":"Irving Development","description":"Just another WordPress site","potentialAction":[{"@type":"SearchAction","target":"https://irving.alley.test/?s={search_term_string}","query-input":"required name=search_term_string"}],"inLanguage":"en-US"},{"@type":"CollectionPage","@id":"https://irving.alley.test/#webpage","url":"https://irving.alley.test/","name":"Irving Development - Just another WordPress site","isPartOf":{"@id":"https://irving.alley.test/#website"},"description":"Just another WordPress site","inLanguage":"en-US"}]}',
					]
				],
			],
			parse_html( $this->example_markup, [ 'script' ] )
		);
	}
}
