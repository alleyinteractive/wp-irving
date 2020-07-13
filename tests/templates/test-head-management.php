<?php
/**
 * Class Test_Head_Management
 *
 * @package WP_Irving
 */

namespace WP_Irving\Templates;

use WP_Irving\Components\Component;
use WP_UnitTestCase;

/**
 * Test head functionality.
 *
 * @group templates
 */
class Test_Head_Management extends WP_UnitTestCase {

	/**
	 * Test attachment
	 *
	 * @var int attachment ID.
	 */
	public static $attachment_id;

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
	}

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setup();

		// Disable date organized uploads.
		add_filter( 'upload_dir', [ $this, 'upload_dir_no_subdir' ] );

		// Set the site icon.
		update_option( 'site_icon', $this->get_attachment_id() );
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
	 * Return the result from a clean run of `setup_head()`.
	 *
	 * @return array
	 */
	public function get_head_setup_result(): array {
		return setup_head(
			$this->get_blank_data_array(),
			new \WP_Query(),
			'site'
		);
	}

	/**
	 * Test that the <head> setup returns the correct component in both the
	 * `defaults` and `page` component arrays.
	 */
	public function test_default_head() {

		// Run `setup_head()` on empty/blank data.
		$component_endpoint_result = $this->get_head_setup_result();

		// `defaults` should have a <head> component.
		$this->assertEquals(
			new Component(
				'irving/head',
				[
					'config' => [
						'context' => 'defaults',
					],
				]
			),
			$component_endpoint_result['defaults'][0],
			'First element in the `defaults` component array is not `irving/head`.'
		);

		$this->assertEquals(
			new Component(
				'irving/head',
				[
					'config' => [
						'context' => 'page',
					],
				]
			),
			$component_endpoint_result['page'][0],
			'First element in the `page` component array is not `irving/head`.'
		);
	}

	/**
	 * Test disabling the automated `irving/head` management.
	 */
	public function test_disabling_head() {

		// Disable functionality.
		add_filter( 'wp_irving_setup_head', '__return_false' );

		// Run `setup_head()` on empty/blank data.
		$component_endpoint_result = $this->get_head_setup_result();

		// Nothing should have happened.
		$this->assertEquals( [], $component_endpoint_result['defaults'], 'Defaults array was not empty.' );
		$this->assertEquals( [], $component_endpoint_result['page'], 'Page array was not empty.' );

		// Re-enable functionality.
		add_filter( 'wp_irving_setup_head', '__return_true' );
	}

	/**
	 * Test using a filter on the `irving/head` children.
	 */
	public function test_children_filter() {

		// Add a fiter that modifies the `irving/head` component children.
		add_filter( 'wp_irving_component_children', [ $this, 'example_children_callback' ], 10, 3 );

		// Test that the filter has been applied.
		$component_endpoint_result = $this->get_head_setup_result();

		// Test `defaults`.
		$this->assertEquals(
			[
				new Component(
					'irving/head',
					[
						'config'   => [
							'context' => 'defaults',
						],
						'children' => [
							new Component(
								'meta',
								[
									'config' => [
										'context' => 'defaults',
									],
								]
							),
						],
					]
				),
			],
			$component_endpoint_result['defaults'],
			'`defaults` component array did not have the filtered changes.'
		);

		// Test `page`.
		$this->assertEquals(
			[
				new Component(
					'irving/head',
					[
						'config'   => [
							'context' => 'page',
						],
						'children' => [
							new Component(
								'meta',
								[
									'config' => [
										'context' => 'page',
									],
								]
							),
						],
					]
				),
			],
			$component_endpoint_result['page'],
			'`page` component array did not have the filtered changes.'
		);

		// Remove the filter.
		remove_filter( 'wp_irving_component_children', [ $this, 'example_children_callback' ], 10 );
	}

	/**
	 * Helper function that can be used to test the
	 * `wp_irving_component_children` filter.
	 *
	 * @param array $children `irving/head` children.
	 * @param array $config   `irving/head` config.
	 * @return array `irving/head` children.
	 */
	public function example_children_callback( array $children, array $config, string $name ): array {
		return [
			new Component(
				'meta',
				[
					'config' => [
						'context' => $config['context'] ?? '',
					],
				]
			),
		];
	}

	/**
	 * Test `get_favicon_markup()`.
	 *
	 * This tests the value of WordPress' favicon markup.
	 */
	public function test_get_favicon_markup() {
		$this->assertEquals(
			'<link rel="icon" href="http://example.org/wp-content/uploads/test-image.jpg" sizes="32x32" />
<link rel="icon" href="http://example.org/wp-content/uploads/test-image.jpg" sizes="192x192" />
<link rel="apple-touch-icon" href="http://example.org/wp-content/uploads/test-image.jpg" />
<meta name="msapplication-TileImage" content="http://example.org/wp-content/uploads/test-image.jpg" />',
			get_favicon_markup(),
			'Favicon markup incorrect'
		);
	}

	/**
	 * Test `inject_favicon()`.
	 *
	 * This method is used as a children callback on the <head> component.
	 */
	public function tests_inject_favicon() {
		$this->assertEquals(
			[
				new Component(
					'link',
					[
						'config' => [
							'href'  => 'http://example.org/wp-content/uploads/test-image.jpg',
							'rel'   => 'icon',
							'sizes' => '32x32',
						],
					]
				),
				new Component(
					'link',
					[
						'config' => [
							'href'  => 'http://example.org/wp-content/uploads/test-image.jpg',
							'rel'   => 'icon',
							'sizes' => '192x192',
						],
					]
				),
				new Component(
					'link',
					[
						'config' => [
							'href' => 'http://example.org/wp-content/uploads/test-image.jpg',
							'rel'  => 'apple-touch-icon',
						],
					]
				),
				new Component(
					'meta',
					[
						'config' => [
							'content' => 'http://example.org/wp-content/uploads/test-image.jpg',
							'name'    => 'msapplication-TileImage',
						],
					]
				),
			],
			inject_favicon( [] ),
			'Parsed favicon markup incorrect'
		);
	}

}
