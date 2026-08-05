<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Wp_Theme_Mover
 */

$_composer_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $_composer_autoload ) ) {
	echo "Composer dependencies are missing. Run 'composer install' (or ./bin/test.sh phpunit).\n";
	exit( 1 );
}

require_once $_composer_autoload;

// WordPress' own bootstrap looks the polyfills up here; without it, it aborts
// asking for wordpress-develop's vendor directory, which we don't install.
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find the WordPress test suite in {$_tests_dir}.\n";
	echo "Run bin/install-wp-tests.sh (or ./bin/test.sh phpunit, which does it for you).\n";
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Activate the theme before WordPress loads theme functions, so functions.php
 * runs through the normal path and its init hooks land before init fires.
 * bin/install-wp-tests.sh symlinks the checkout into the test install's themes
 * directory under this name.
 */
tests_add_filter(
	'muplugins_loaded',
	function () {
		$theme = function () {
			return 'wp-softcatala';
		};

		add_filter( 'stylesheet', $theme );
		add_filter( 'template', $theme );

		// ACF is a hard dependency of the theme (see acf-json/): several hooks
		// call get_field() unconditionally. install-wp-tests.sh puts it here.
		$acf = rtrim( getenv( 'WP_CORE_DIR' ) ?: '/tmp/wordpress', '/' ) . '/wp-content/plugins/advanced-custom-fields/acf.php';

		if ( file_exists( $acf ) ) {
			require_once $acf;
		}
	}
);

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
