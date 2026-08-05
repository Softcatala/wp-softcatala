<?php

include_once(__DIR__ . '/../functions.php');

class SCTests extends WP_UnitTestCase {

	function set_up() {

		parent::set_up();

		// WP_UnitTestCase unregisters post types, taxonomies and meta keys
		// between tests, so everything the theme registers on init would be
		// missing for every test after the first. Re-firing init restores them.
		do_action( 'init' );

		switch_theme( 'wp-softcatala', 'wp-softcatala' );

	} // end setup

	function test_empty_method() {

	}
}