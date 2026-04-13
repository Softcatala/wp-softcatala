<?php
/**
 * Tests for Softcatala\Providers\Tasques.
 *
 * Covers get_ordered_estats(), group_by_estat(), and get_filter_options() using
 * WP_UnitTestCase fixtures. get_all_for_board() and get_internal_projecte_ids()
 * depend on ACF (get_field) which is not available in the test environment without
 * a stub; those methods are verified via integration testing on the live site.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Providers\Tasques;

/**
 * Class TasquesProviderTest
 */
class TasquesProviderTest extends SCTests {

	/**
	 * Test that get_ordered_estats returns an empty array when no terms exist.
	 */
	function test_get_ordered_estats_returns_empty_when_no_terms() {
		$estats = Tasques::get_ordered_estats();
		$this->assertIsArray( $estats );
		$this->assertEmpty( $estats );
	}

	/**
	 * Test that get_ordered_estats returns terms sorted by the order meta.
	 */
	function test_get_ordered_estats_sorts_by_order_meta() {
		// Insert terms in reverse order to verify sorting.
		$feta = wp_insert_term( 'Feta', 'estat_tasca' );
		update_term_meta( $feta['term_id'], 'order', 4 );

		$en_curs = wp_insert_term( 'En curs', 'estat_tasca' );
		update_term_meta( $en_curs['term_id'], 'order', 2 );

		$pendent = wp_insert_term( 'Pendent', 'estat_tasca' );
		update_term_meta( $pendent['term_id'], 'order', 1 );

		$estats = Tasques::get_ordered_estats();

		$this->assertCount( 3, $estats );
		$this->assertSame( 'Pendent', $estats[0]->name );
		$this->assertSame( 'En curs', $estats[1]->name );
		$this->assertSame( 'Feta', $estats[2]->name );
	}

	/**
	 * Test that get_ordered_estats uses alphabetical name as tiebreaker when order is equal.
	 */
	function test_get_ordered_estats_alphabetical_tiebreaker() {
		$b = wp_insert_term( 'Beta', 'estat_tasca' );
		update_term_meta( $b['term_id'], 'order', 1 );

		$a = wp_insert_term( 'Alpha', 'estat_tasca' );
		update_term_meta( $a['term_id'], 'order', 1 );

		$estats = Tasques::get_ordered_estats();

		$this->assertCount( 2, $estats );
		$this->assertSame( 'Alpha', $estats[0]->name );
		$this->assertSame( 'Beta', $estats[1]->name );
	}

	/**
	 * Test group_by_estat puts tasks with no term into 'unassigned'.
	 */
	function test_group_by_estat_unassigned_bucket() {
		$term_result = wp_insert_term( 'Pendent', 'estat_tasca' );
		$term        = get_term( $term_result['term_id'], 'estat_tasca' );

		$post_with_term    = $this->factory->post->create( array( 'post_type' => 'tasca', 'post_status' => 'publish' ) );
		$post_without_term = $this->factory->post->create( array( 'post_type' => 'tasca', 'post_status' => 'publish' ) );

		wp_set_post_terms( $post_with_term, array( $term->term_id ), 'estat_tasca' );

		$tasks  = array( get_post( $post_with_term ), get_post( $post_without_term ) );
		$estats = array( $term );

		$grouped = Tasques::group_by_estat( $tasks, $estats );

		$this->assertArrayHasKey( 'pendent', $grouped );
		$this->assertArrayHasKey( 'unassigned', $grouped );
		$this->assertCount( 1, $grouped['pendent'] );
		$this->assertCount( 1, $grouped['unassigned'] );
		$this->assertSame( $post_without_term, $grouped['unassigned'][0]->ID );
	}

	/**
	 * Test group_by_estat preserves term order from $estats array.
	 */
	function test_group_by_estat_preserves_estat_order() {
		$t1 = wp_insert_term( 'Pendent', 'estat_tasca' );
		$t2 = wp_insert_term( 'Feta', 'estat_tasca' );

		$term1 = get_term( $t1['term_id'], 'estat_tasca' );
		$term2 = get_term( $t2['term_id'], 'estat_tasca' );

		// Pass in deliberate order: Feta first, then Pendent.
		$estats  = array( $term2, $term1 );
		$grouped = Tasques::group_by_estat( array(), $estats );

		$keys = array_keys( $grouped );
		$this->assertSame( 'feta', $keys[0] );
		$this->assertSame( 'pendent', $keys[1] );
		$this->assertSame( 'unassigned', $keys[2] );
	}

	/**
	 * Test get_filter_options returns empty arrays when tasks list is empty.
	 */
	function test_get_filter_options_empty_tasks() {
		$options = Tasques::get_filter_options( array() );

		$this->assertIsArray( $options );
		$this->assertArrayHasKey( 'projectes', $options );
		$this->assertArrayHasKey( 'assignees', $options );
		$this->assertArrayHasKey( 'milestones', $options );
		$this->assertArrayHasKey( 'tags', $options );

		$this->assertEmpty( $options['projectes'] );
		$this->assertEmpty( $options['assignees'] );
		$this->assertEmpty( $options['milestones'] );
		$this->assertEmpty( $options['tags'] );
	}

	/**
	 * Test get_filter_options extracts tag slugs from tasks.
	 */
	function test_get_filter_options_extracts_tags() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'tasca', 'post_status' => 'publish' ) );

		$tag = wp_insert_term( 'català', 'tag_tasca' );
		wp_set_post_terms( $post_id, array( $tag['term_id'] ), 'tag_tasca' );

		$options = Tasques::get_filter_options( array( get_post( $post_id ) ) );

		$this->assertCount( 1, $options['tags'] );
		$this->assertSame( 'català', $options['tags'][0]['name'] );
	}

	/**
	 * Test that duplicate tags across multiple tasks are deduplicated.
	 */
	function test_get_filter_options_deduplicates_tags() {
		$post1 = $this->factory->post->create( array( 'post_type' => 'tasca', 'post_status' => 'publish' ) );
		$post2 = $this->factory->post->create( array( 'post_type' => 'tasca', 'post_status' => 'publish' ) );

		$tag = wp_insert_term( 'shared-tag', 'tag_tasca' );
		wp_set_post_terms( $post1, array( $tag['term_id'] ), 'tag_tasca' );
		wp_set_post_terms( $post2, array( $tag['term_id'] ), 'tag_tasca' );

		$options = Tasques::get_filter_options( array( get_post( $post1 ), get_post( $post2 ) ) );

		$this->assertCount( 1, $options['tags'] );
	}
}
