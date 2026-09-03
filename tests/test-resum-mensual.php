<?php
/**
 * Tests for the resum_mensual CPT: the properties SoftcatalaBot depends on
 * when publishing monthly summaries over the REST API.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class ResumMensualTest
 */
class ResumMensualTest extends SCTests {

	/**
	 * Ensure the resum_mensual post type is registered.
	 */
	function test_resum_mensual_post_type_exists() {
		$this->assertTrue( post_type_exists( 'resum_mensual' ) );
	}

	/**
	 * The bot POSTs to /wp-json/wp/v2/resums-mensuals.
	 */
	function test_resum_mensual_rest_base() {
		$obj = get_post_type_object( 'resum_mensual' );
		$this->assertTrue( $obj->show_in_rest );
		$this->assertEquals( 'resums-mensuals', $obj->rest_base );
	}

	/**
	 * Not public (out of the main feed), but the permalink must resolve.
	 */
	function test_resum_mensual_visibility_flags() {
		$obj = get_post_type_object( 'resum_mensual' );
		$this->assertFalse( $obj->public );
		$this->assertTrue( $obj->publicly_queryable );
		$this->assertTrue( $obj->exclude_from_search );
	}

	/**
	 * The bot sets title and content.
	 */
	function test_resum_mensual_supports_title_and_editor() {
		$this->assertTrue( post_type_supports( 'resum_mensual', 'title' ) );
		$this->assertTrue( post_type_supports( 'resum_mensual', 'editor' ) );
	}

	/**
	 * capability_type 'post' lets the app-password editor create summaries.
	 */
	function test_resum_mensual_capability_type() {
		$obj = get_post_type_object( 'resum_mensual' );
		$this->assertEquals( 'post', $obj->capability_type );
		$this->assertTrue( $obj->map_meta_cap );
	}

	/**
	 * Summaries never show up in site search results.
	 */
	function test_resum_mensual_excluded_from_search() {
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'resum_mensual',
				'post_title'  => 'Resum mensual 2026-08',
				'post_status' => 'publish',
			)
		);

		$found = get_posts(
			array(
				's'           => 'Resum mensual 2026-08',
				'post_type'   => 'any',
				'fields'      => 'ids',
				'post_status' => 'publish',
			)
		);

		$this->assertNotContains( $post_id, $found );
	}
}
