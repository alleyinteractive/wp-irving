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
		add_filter( 'upload_dir', 'WP_Irving\Test_Helpers::upload_dir_no_subdir' );

		// Set the site icon.
		update_option( 'site_icon', $this->get_attachment_id() );
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
						'context' => 'site',
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
							'context' => 'site',
						],
						'children' => [
							new Component(
								'meta',
								[
									'config' => [
										'context' => 'site',
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
	}

	/**
	 * Helper function that can be used to test the
	 * `wp_irving_component_children` filter.
	 *
	 * @param array  $children Children for this component.
	 * @param array  $config   Config for this component.
	 * @param string $name     Name of this component.
	 * @return array `irving/head` children.
	 */
	public function example_children_callback( array $children, array $config, string $name ): array {

		// Ony run this action on the `irving/head` component.
		if ( 'irving/head' !== $name ) {
			return $children;
		}

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
			'<link rel="icon" href="' . $this->get_attachment_url() . '" sizes="32x32" />
<link rel="icon" href="' . $this->get_attachment_url() . '" sizes="192x192" />
<link rel="apple-touch-icon" href="' . $this->get_attachment_url() . '" />
<meta name="msapplication-TileImage" content="' . $this->get_attachment_url() . '" />',
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
							'href'  => $this->get_attachment_url(),
							'rel'   => 'icon',
							'sizes' => '32x32',
						],
					]
				),
				new Component(
					'link',
					[
						'config' => [
							'href'  => $this->get_attachment_url(),
							'rel'   => 'icon',
							'sizes' => '192x192',
						],
					]
				),
				new Component(
					'link',
					[
						'config' => [
							'href' => $this->get_attachment_url(),
							'rel'  => 'apple-touch-icon',
						],
					]
				),
				new Component(
					'meta',
					[
						'config' => [
							'content' => $this->get_attachment_url(),
							'name'    => 'msapplication-TileImage',
						],
					]
				),
			],
			inject_favicon( [], [], 'irving/head' ),
			'Parsed favicon markup incorrect'
		);
	}
}
