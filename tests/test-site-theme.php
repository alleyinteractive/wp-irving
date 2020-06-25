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
	];

	/**
	 * Set up test data.
	 */
	public function setUp() {
		parent::setUp();

		// Replace the dynamically loaded data with local data.
		add_filter(
			'wp_irving_setup_site_theme',
			function( $theme ) {
				return $this->site_theme;
			}
		);
	}

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
		$this->assertEquals(
			Templates\get_site_theme( $selector, $default ),
			$expected_value,
			$message
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
		];
	}
}
