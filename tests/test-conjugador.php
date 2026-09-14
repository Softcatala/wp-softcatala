<?php
/**
 * The conjugador's not-found responses echo the searched term, and the
 * templates print that HTML raw, so the term has to be escaped here.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class ConjugadorTest
 */
class ConjugadorTest extends SCTests {

	const PAYLOAD = '<img src=x onerror=alert(1)>';

	/**
	 * A rest client whose every lookup misses.
	 */
	private function missing_client() {
		return new class {
			public function get( $url, $api_key = false ) {
				return array( 'error' => false, 'code' => 404, 'result' => '' );
			}
		};
	}

	function test_multi_letter_index_search_escapes_the_term() {
		$result = ( new SC_Conjugador( $this->missing_client() ) )->get_lletra( self::PAYLOAD );

		$this->assertEquals( 404, $result->status );
		$this->assertStringNotContainsString( '<img', $result->html );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $result->html );
	}

	function test_unknown_verb_escapes_the_term() {
		$result = ( new SC_Conjugador( $this->missing_client() ) )->get_verb( self::PAYLOAD );

		$this->assertEquals( 404, $result->status );
		$this->assertStringNotContainsString( '<img', $result->html );
		$this->assertStringNotContainsString( '<img', $result->content_title );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $result->html );
	}
}
