<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WP_Irving
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

// Constant to determine when tests are running.
define( 'WP_IRVING_TEST', true );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	// PHPCS is complaining about this line not being translated, but it not really relevant for this file.
	// phpcs:disable
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
	// phpcs:enable
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/wp-irving.php';
	require dirname( __DIR__, 2 ) . '/safe-redirect-manager/safe-redirect-manager.php';
	require dirname( __DIR__, 2 ) . '/wpcom-legacy-redirector/wpcom-legacy-redirector.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Include test helpers
require 'tests/test-helpers.php';
