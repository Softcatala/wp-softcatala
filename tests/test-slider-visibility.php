<?php
/**
 * Regression coverage for slider visibility.
 */
require_once( 'sc_tests.php' );

class SliderVisibilityTest extends SCTests {

	public function test_slider_is_editable_but_not_publicly_queryable() {
		$post_type = get_post_type_object( 'slider' );

		$this->assertNotNull( $post_type );
		$this->assertTrue( $post_type->show_ui );
		$this->assertFalse( $post_type->public );
		$this->assertFalse( $post_type->publicly_queryable );
		$this->assertFalse( $post_type->show_in_nav_menus );
	}
}
