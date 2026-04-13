<?php
/**
 * Tests for the Tasca and Milestone CPTs, estat_tasca / tag_tasca taxonomies,
 * term-seeder, pre_delete guard, and template_redirect for task permalinks.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class TascaTest
 */
class TascaTest extends SCTests {

	/**
	 * Ensure the tasca post type is registered.
	 */
	function test_tasca_post_type_exists() {
		$this->assertTrue( post_type_exists( 'tasca' ) );
	}

	/**
	 * Ensure the milestone post type is registered.
	 */
	function test_milestone_post_type_exists() {
		$this->assertTrue( post_type_exists( 'milestone' ) );
	}

	/**
	 * Ensure the estat_tasca taxonomy is registered.
	 */
	function test_estat_tasca_taxonomy_exists() {
		$this->assertTrue( taxonomy_exists( 'estat_tasca' ) );
	}

	/**
	 * Ensure the tag_tasca taxonomy is registered.
	 */
	function test_tag_tasca_taxonomy_exists() {
		$this->assertTrue( taxonomy_exists( 'tag_tasca' ) );
	}

	/**
	 * tasca is publicly_queryable (required for the /tasques/ archive).
	 */
	function test_tasca_is_publicly_queryable() {
		$obj = get_post_type_object( 'tasca' );
		$this->assertTrue( $obj->publicly_queryable );
	}

	/**
	 * tasca is not public (suppresses nav menus, search, sitemaps).
	 */
	function test_tasca_is_not_public() {
		$obj = get_post_type_object( 'tasca' );
		$this->assertFalse( $obj->public );
	}

	/**
	 * tasca archive slug is 'tasques'.
	 */
	function test_tasca_has_archive_tasques() {
		$obj = get_post_type_object( 'tasca' );
		$this->assertEquals( 'tasques', $obj->has_archive );
	}

	/**
	 * tasca uses the TascaRestController class.
	 */
	function test_tasca_rest_controller_class() {
		$obj = get_post_type_object( 'tasca' );
		$this->assertEquals( 'Softcatala\TypeRegisters\TascaRestController', $obj->rest_controller_class );
	}

	/**
	 * Seeder creates exactly 4 default estat_tasca terms.
	 */
	function test_seeder_creates_default_terms() {
		// Ensure no terms exist before seeding.
		$existing = get_terms( array( 'taxonomy' => 'estat_tasca', 'hide_empty' => false ) );
		foreach ( $existing as $term ) {
			wp_delete_term( $term->term_id, 'estat_tasca' );
		}

		sc_seed_estat_tasca();

		$terms = get_terms( array( 'taxonomy' => 'estat_tasca', 'hide_empty' => false ) );
		$this->assertCount( 4, $terms );
	}

	/**
	 * Seeder is idempotent — calling it twice does not create duplicates.
	 */
	function test_seeder_is_idempotent() {
		// Reset.
		$existing = get_terms( array( 'taxonomy' => 'estat_tasca', 'hide_empty' => false ) );
		foreach ( $existing as $term ) {
			wp_delete_term( $term->term_id, 'estat_tasca' );
		}

		sc_seed_estat_tasca();
		sc_seed_estat_tasca();

		$terms = get_terms( array( 'taxonomy' => 'estat_tasca', 'hide_empty' => false ) );
		$this->assertCount( 4, $terms );
	}

	/**
	 * Default terms have order meta set (1–4).
	 */
	function test_seeder_sets_order_meta() {
		// Reset.
		$existing = get_terms( array( 'taxonomy' => 'estat_tasca', 'hide_empty' => false ) );
		foreach ( $existing as $term ) {
			wp_delete_term( $term->term_id, 'estat_tasca' );
		}

		sc_seed_estat_tasca();

		$terms = get_terms( array( 'taxonomy' => 'estat_tasca', 'hide_empty' => false ) );
		$orders = array();
		foreach ( $terms as $term ) {
			$order = (int) get_term_meta( $term->term_id, 'order', true );
			$orders[] = $order;
		}
		sort( $orders );
		$this->assertEquals( array( 1, 2, 3, 4 ), $orders );
	}

	/**
	 * pre_delete_term prevents deletion of an estat_tasca term with assigned tasks.
	 */
	function test_pre_delete_term_blocks_deletion_with_tasks() {
		// Create an estat_tasca term.
		$term_result = wp_insert_term( 'Test Estat', 'estat_tasca' );
		$this->assertNotWPError( $term_result );
		$term_id = $term_result['term_id'];

		// Create a tasca post assigned to this term.
		$task_id = wp_insert_post( array(
			'post_type'   => 'tasca',
			'post_title'  => 'Test Task',
			'post_status' => 'publish',
		) );
		wp_set_post_terms( $task_id, array( $term_id ), 'estat_tasca' );

		// Attempt to delete the term — should fail.
		$result = wp_delete_term( $term_id, 'estat_tasca' );
		$this->assertWPError( $result );

		// Clean up.
		wp_delete_post( $task_id, true );
		wp_delete_term( $term_id, 'estat_tasca' );
	}

	/**
	 * pre_delete_term allows deletion of an estat_tasca term with no assigned tasks.
	 */
	function test_pre_delete_term_allows_deletion_without_tasks() {
		$term_result = wp_insert_term( 'Empty Estat', 'estat_tasca' );
		$this->assertNotWPError( $term_result );
		$term_id = $term_result['term_id'];

		$result = wp_delete_term( $term_id, 'estat_tasca' );
		$this->assertNotWPError( $result );
	}

	/**
	 * Order meta is registered for estat_tasca terms.
	 */
	function test_order_meta_registered() {
		$registered = registered_meta_key_exists( 'term', 'order', 'estat_tasca' );
		$this->assertTrue( $registered );
	}
}
