<?php
/**
 * Class Test_HTML_Markup
 *
 * @package WP_Irving
 */

namespace WP_Irving\Components;

use \WP_Irving\Components\Component;
use WP_UnitTestCase;

/**
 * Test HTML Markup utilities.
 *
 * @group templates
 */
class Test_HTML_Markup extends WP_UnitTestCase {

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
	 * Test the `html_to_components()` utility.
	 *
	 * @dataProvider get_html_examples
	 *
	 * @param  string $markup   Markup to turn into components.
	 * @param  array  $tags     Which elements in the markup should be parsed.
	 * @param  array  $expected Expected components.
	 * @param  string $message  Message if assertion fails.
	 */
	public function test_html_to_components( string $markup, array $tags, array $expected, string $message ) {
		$this->assertEquals(
			$expected,
			html_to_components( $markup, $tags ),
			$message
		);
	}

	/**
	 * Data provider for test_html_to_components.
	 *
	 * @return array
	 */
	public function get_html_examples() {
		return [
			[
				// Test a <title> element.
				'<title>Hello World</title>',
				[ 'title' ],
				[
					new Component(
						'title',
						[
							'children' => [
								'Hello World',
							],
						]
					),
				],
				'<title> markup not parsed correctly.',
			],
			[
				// Test a <meta> element.
				'<meta name="description" content="Hello World" />',
				[ 'meta' ],
				[
					new Component(
						'meta',
						[
							'config' => [
								'content' => 'Hello World',
								'name'    => 'description',
							],
						]
					),
				],
				'<meta> markup not parsed correctly.',
			],
			[
				// Test a <link> element.
				'<link rel="canonical" href="https://irvingjs.com/" />',
				[ 'link' ],
				[
					new Component(
						'link',
						[
							'config' => [
								'rel'  => 'canonical',
								'href' => 'https://irvingjs.com/',
							],
						]
					),
				],
				'<link> markup not parsed correctly.',
			],
			[
				// Test an <a> element.
				'<a href="/hello-world/">Hello World</a>',
				[ 'a' ],
				[
					new Component(
						'a',
						[
							'config'   => [
								'href' => '/hello-world/',
							],
							'children' => [
								'Hello World',
							],
						]
					),
				],
				'<a> markup not parsed correctly.',
			],
			[
				// Test a search for <link> when none exist.
				'<meta name="description" content="Hello World" />',
				[ 'link' ],
				[],
				'Tag selector not working as expected.',
			],
			[
				// Test multiple <meta> elements..
				'<meta name="description" content="Just another WordPress site" />
				<meta name="robots" content="index, follow" />',
				[ 'meta' ],
				[
					new Component(
						'meta',
						[
							'config' => [
								'name'    => 'description',
								'content' => 'Just another WordPress site',
							],
						]
					),
					new Component(
						'meta',
						[
							'config' => [
								'name'    => 'robots',
								'content' => 'index, follow',
							],
						]
					),
				],
				'Selecting multiple tags not working as expected.',
			],
			[
				// Test multiple tags.
				$this->example_markup,
				[ 'title', 'meta', 'link' ],
				[
					new Component(
						'title',
						[
							'children' => [
								'Irving Development - Just another WordPress site',
							],
						]
					),
					new Component(
						'meta',
						[
							'config' => [
								'content' => 'Just another WordPress site',
								'name'    => 'description',
							],
						]
					),
					new Component(
						'meta',
						[
							'config' => [
								'content' => 'index, follow',
								'name'    => 'robots',
							],
						]
					),
					new Component(
						'meta',
						[
							'config' => [
								'content' => 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
								'name'    => 'googlebot',
							],
						]
					),
					new Component(
						'meta',
						[
							'config' => [
								'content' => 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
								'name'    => 'bingbot',
							],
						]
					),
					new Component(
						'link',
						[
							'config' => [
								'href' => 'https://irving.alley.test/',
								'rel'  => 'canonical',
							],
						]
					),
				],
				'Tag selector not working as expected.',
			],
			[
				// Test the return order of multiple tags.
				$this->example_markup,
				[ 'link', 'meta', 'title' ],
				[
					new Component(
						'link',
						[
							'config' => [
								'href' => 'https://irving.alley.test/',
								'rel'  => 'canonical',
							],
						]
					),
					new Component(
						'meta',
						[
							'config' => [
								'content' => 'Just another WordPress site',
								'name'    => 'description',
							],
						]
					),
					new Component(
						'meta',
						[
							'config' => [
								'content' => 'index, follow',
								'name'    => 'robots',
							],
						]
					),
					new Component(
						'meta',
						[
							'config' => [
								'content' => 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
								'name'    => 'googlebot',
							],
						]
					),
					new Component(
						'meta',
						[
							'config' => [
								'content' => 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
								'name'    => 'bingbot',
							],
						]
					),
					new Component(
						'title',
						[
							'children' => [
								'Irving Development - Just another WordPress site',
							],
						]
					),
				],
				'Tag selector not working as expected.',
			],
		];
	}
}
