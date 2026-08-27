<?php
/**
 * Regression coverage for custom rewrite rules.
 */
require_once( 'sc_tests.php' );

class RewriteRulesTest extends SCTests {

	public function test_custom_rewrites_match_complete_paths() {
		$rules = sc_custom__rewrite_rules( array() );

		foreach ( array_keys( $rules ) as $pattern ) {
			$this->assertStringStartsWith( '^', $pattern );
			$this->assertStringEndsWith( '$', $pattern );
		}
	}

	public function test_program_pagination_only_accepts_numbers() {
		$rules = sc_custom__rewrite_rules( array() );

		$this->assertArrayHasKey( '^programes/p/([^/]+)/pagina/([0-9]+)/?$', $rules );
		$this->assertArrayNotHasKey( '^programes/p/([^/]+)/pagina/([^/]+)/?$', $rules );
	}
}
