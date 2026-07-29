<?php
/**
 * Tests for closing comments on archived programes and projectes
 * (classificacio: arxivat).
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class ArchivedCommentsTest
 */
class ArchivedCommentsTest extends SCTests {

	/**
	 * Create a published post of the given type with open comments,
	 * optionally tagged with the arxivat classificacio term.
	 *
	 * @param string $post_type Post type slug.
	 * @param bool   $arxivat   Whether to assign the arxivat term.
	 * @return int Post ID.
	 */
	private function create_post( $post_type, $arxivat ) {
		$post_id = $this->factory->post->create(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'comment_status' => 'open',
			)
		);

		if ( $arxivat ) {
			wp_set_object_terms( $post_id, 'arxivat', 'classificacio' );
		}

		return $post_id;
	}

	/**
	 * Comments stay open on a non-archived programa.
	 */
	function test_comments_open_on_active_programa() {
		$post_id = $this->create_post( 'programa', false );
		$this->assertTrue( comments_open( $post_id ) );
	}

	/**
	 * Comments are closed on an archived programa.
	 */
	function test_comments_closed_on_archived_programa() {
		$post_id = $this->create_post( 'programa', true );
		$this->assertFalse( comments_open( $post_id ) );
	}

	/**
	 * Comments stay open on a non-archived projecte.
	 */
	function test_comments_open_on_active_projecte() {
		$post_id = $this->create_post( 'projecte', false );
		$this->assertTrue( comments_open( $post_id ) );
	}

	/**
	 * Comments are closed on an archived projecte.
	 */
	function test_comments_closed_on_archived_projecte() {
		$post_id = $this->create_post( 'projecte', true );
		$this->assertFalse( comments_open( $post_id ) );
	}

	/**
	 * The arxivat term on another post type does not close comments.
	 */
	function test_comments_open_on_archived_regular_post() {
		$post_id = $this->factory->post->create(
			array(
				'post_status'    => 'publish',
				'comment_status' => 'open',
			)
		);
		wp_set_object_terms( $post_id, 'arxivat', 'classificacio' );

		$this->assertTrue( comments_open( $post_id ) );
	}

	/**
	 * Unarchiving a projecte reopens its comments (filter-based, not stored).
	 */
	function test_comments_reopen_after_unarchiving() {
		$post_id = $this->create_post( 'projecte', true );
		$this->assertFalse( comments_open( $post_id ) );

		wp_set_object_terms( $post_id, array(), 'classificacio' );
		$this->assertTrue( comments_open( $post_id ) );
	}
}
