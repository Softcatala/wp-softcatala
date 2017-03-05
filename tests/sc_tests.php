<?php

include_once(__DIR__ . '/../functions.php');

class SCTests extends WP_UnitTestCase {

	function setUp() {

		parent::setUp();
		switch_theme( 'wp-softcatala', 'wp-softcatala' );

	} // end setup

	function test_empty_method() {

	}
}