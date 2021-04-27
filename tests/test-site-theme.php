<?php
/**
 * Class Test_Site_Theme
 *
 * @package WP_Irving
 */

namespace WP_Irving;

use WP_Irving\Templates;
use WP_UnitTestCase;

/**
 * Test site theme functionality.
 *
 * @group templates
 */
class Test_Site_Theme extends WP_UnitTestCase {

	/**
	 * Example theme.
	 *
	 * @var array
	 */
	public $site_theme = [
		'colors' => [
			'black' => '#000000',
			'white' => '#FFFFFF',
		],
		'brand'  => [
			'primary' => [
				'light' => '#FFFFFF',
				'main'  => '#EEEEEE',
				'dark'  => '#DDDDDD',
			],
		],
		'nested' => [
			'first'  => 'colors.white',
			'second' => [
				'value' => 'colors.white',
			],
			'third'  => [
				'value' => 'nested.second.value',
			],
		],
	];

	/**
	 * Test the basic functionality of the `get_site_theme()` method.
	 *
	 * @dataProvider get_site_theme_selectors
	 *
	 * @param string $selector       Dot notation string to select a theme
	 *                               value.
	 * @param mixed  $default        Default value when the selector does not
	 *                               return a valid path/value.
	 * @param mixed  $expected_value Value expected to be returned using the
	 *                               selector.
	 * @param string $message        Messagae if the test fails.
	 */
	public function test_get_site_theme( string $selector, $default, $expected_value, string $message ) {

		add_filter(
			'wp_irving_setup_site_theme',
			function( $theme ) {
				return $this->site_theme;
			}
		);

		$this->assertEquals(
			Templates\get_site_theme( $selector, $default ),
			$expected_value,
			$message
		);

		remove_filter(
			'wp_irving_setup_site_theme',
			function( $theme ) {
				return $this->site_theme;
			}
		);
	}

	/**
	 * Data provider to test the `get_site_theme()` method.
	 *
	 * @return array
	 */
	public function get_site_theme_selectors(): array {
		return [
			[
				'',
				null,
				$this->site_theme,
				'Could not select entire theme.',
			],
			[
				'colors',
				null,
				$this->site_theme['colors'],
				'Could not select a top level array',
			],
			[
				'colors.black',
				null,
				$this->site_theme['colors']['black'],
				'Could not select a single value.',
			],
			[
				'brand',
				null,
				$this->site_theme['brand'],
				'Could not select a top level array.',
			],
			[
				'brand.primary',
				null,
				$this->site_theme['brand']['primary'],
				'Could not select a nested array.',
			],
			[
				'brand.primary.main',
				null,
				$this->site_theme['brand']['primary']['main'],
				'Could not select a double nested value.',
			],
			[
				'colors.blue',
				'Hello World',
				'Hello World',
				'`Hello World` not returned as default for selector that does not exist.',
			],
			[
				'colors.blue',
				null,
				null,
				'`null` not returned as default for selector that does not exist.',
			],
			[
				'colors.blue.primary',
				null,
				null,
				'`null` not returned as default for selector that does not exist.',
			],
			[
				'colors.blue.primary',
				'Hello World',
				'Hello World',
				'`Hello World` not returned as default for selector that does not exist.',
			],
			[
				'nested.first',
				null,
				'#FFFFFF',
				'`#FFFFFF` not returned as one level nested lookup.',
			],
			[
				'nested.second.value',
				null,
				'#FFFFFF',
				'`#FFFFFF` not returned as two levels nested lookup.',
			],
			[
				'nested.third.value',
				null,
				'#FFFFFF',
				'`#FFFFFF` not returned as double nested lookup.',
			],
		];
	}

	/**
	 * Test child and parent theme support.
	 *
	 * @see ./data/site-theme/ For test data.
	 */
	public function test_get_site_theme_from_json_files() {

		// Adjust the filter to mimic a parent and child theme setup.
		add_filter(
			'wp_irving_site_theme_json_directory_paths',
			function() {
				return [
					__DIR__ . '/data/site-theme/parent',
					__DIR__ . '/data/site-theme/child',
				];
			}
		);

		$this->assertEquals(
			Templates\get_site_theme(),
			[
				'styles' => [
					'is_parent'   => 'no',
					'is_child'    => 'yes',
					'only_parent' => 'yes',
					'only_child'  => 'yes',
					'nested'      => [
						'is_parent'     => 'no',
						'is_child'      => 'yes',
						'only_parent'   => 'yes',
						'only_child'    => 'yes',
						'double_nested' => [
							'is_parent'   => 'no',
							'is_child'    => 'yes',
							'only_parent' => 'yes',
							'only_child'  => 'yes',
						],
					],
				],

			],
			'Child site theme did not inherit parent site theme correctly'
		);
	}
}
