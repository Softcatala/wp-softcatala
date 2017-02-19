<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Wp_Theme_Mover
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
        require'/tmp/wordpress/wp-content/plugins/timber-library/timber.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );


// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
