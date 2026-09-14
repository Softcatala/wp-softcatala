<?php
/**
 * The third step of the add-a-program wizard attaches downloads to a post.
 * It used to accept any post id, so the token issued by step two is the only
 * thing that ties the downloads to the pending programa just created.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class BaixadesTest
 */
class BaixadesTest extends SCTests {

	private function make_programa( $status = 'pending' ) {
		return self::factory()->post->create( array( 'post_type' => 'programa', 'post_status' => $status ) );
	}

	/*
	 * sc_baixada_token_is_valid()
	 */

	function test_issued_token_is_valid_for_its_pending_programa() {
		$post_id = $this->make_programa();
		$token   = sc_issue_baixada_token( $post_id );

		$this->assertTrue( sc_baixada_token_is_valid( $post_id, $token ) );
	}

	function test_wrong_or_missing_token_is_refused() {
		$post_id = $this->make_programa();
		sc_issue_baixada_token( $post_id );

		$this->assertFalse( sc_baixada_token_is_valid( $post_id, 'nope' ) );
		$this->assertFalse( sc_baixada_token_is_valid( $post_id, '' ) );
	}

	function test_token_is_refused_for_another_programa() {
		$mine   = $this->make_programa();
		$theirs = $this->make_programa();
		$token  = sc_issue_baixada_token( $mine );

		$this->assertFalse( sc_baixada_token_is_valid( $theirs, $token ) );
	}

	function test_published_programa_is_refused_even_with_a_token() {
		$post_id = $this->make_programa( 'publish' );
		$token   = sc_issue_baixada_token( $post_id );

		$this->assertFalse( sc_baixada_token_is_valid( $post_id, $token ) );
	}

	function test_other_post_types_are_refused() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'pending' ) );
		$token   = sc_issue_baixada_token( $post_id );

		$this->assertFalse( sc_baixada_token_is_valid( $post_id, $token ) );
		$this->assertFalse( sc_baixada_token_is_valid( 0, $token ) );
	}

	/*
	 * sc_sanitize_baixades()
	 */

	function test_valid_rows_are_kept() {
		list( $rows, $terms ) = sc_sanitize_baixades( json_decode( '{"0":{"url":"https://example.org/a.zip","versio":"1.2","sistema_operatiu":"59","arquitectura":"x86_64"}}' ) );

		$this->assertCount( 1, $rows );
		$this->assertEquals( 'https://example.org/a.zip', $rows[0]['download_url'] );
		$this->assertEquals( '1.2', $rows[0]['download_version'] );
		$this->assertEquals( 'windows', $rows[0]['download_os'] );
		$this->assertEquals( 'x86_64', $rows[0]['arquitectura'] );
		$this->assertEquals( array( '59' ), $terms );
	}

	function test_rows_with_a_bad_url_os_or_architecture_are_dropped() {
		$rows = json_decode( '{
			"0":{"url":"javascript:alert(1)","versio":"1","sistema_operatiu":"59","arquitectura":"x86_64"},
			"1":{"url":"https://example.org/b.zip","versio":"1","sistema_operatiu":"1","arquitectura":"x86_64"},
			"2":{"url":"https://example.org/c.zip","versio":"1","sistema_operatiu":"59","arquitectura":"<b>"},
			"3":{"url":"","versio":"1","sistema_operatiu":"59","arquitectura":"x86"}
		}' );

		list( $kept, $terms ) = sc_sanitize_baixades( $rows );

		$this->assertSame( array(), $kept );
		$this->assertSame( array(), $terms );
	}

	function test_version_is_stripped_of_markup() {
		list( $rows ) = sc_sanitize_baixades( json_decode( '{"0":{"url":"https://example.org/a.zip","versio":"1<b>.2</b>","sistema_operatiu":"64","arquitectura":"generic"}}' ) );

		$this->assertEquals( '1.2', $rows[0]['download_version'] );
	}

	function test_non_array_input_yields_nothing() {
		$this->assertSame( array( array(), array() ), sc_sanitize_baixades( null ) );
		$this->assertSame( array( array(), array() ), sc_sanitize_baixades( 'x' ) );
	}
}
