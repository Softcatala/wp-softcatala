<?php
/**
 * Regression coverage for the curated LLM discovery documents.
 */
class LlmsEndpointTest extends SCTests {

	public function test_registers_exact_routes_and_query_variable() {
		\Softcatala\Content\LlmsEndpoint::register_rewrites();

		global $wp_rewrite;
		$this->assertSame( 'index.php?sc_llms=summary', $wp_rewrite->extra_rules_top['^llms\.txt$'] );
		$this->assertSame( 'index.php?sc_llms=full', $wp_rewrite->extra_rules_top['^llms-full\.txt$'] );
		$this->assertContains( 'sc_llms', apply_filters( 'query_vars', array() ) );
	}

	public function test_requires_the_rewrite_key_and_exact_path() {
		$this->assertTrue( \Softcatala\Content\LlmsEndpoint::matches_request( 'llms.txt', 'summary' ) );
		$this->assertTrue( \Softcatala\Content\LlmsEndpoint::matches_request( 'llms-full.txt', 'full' ) );
		$this->assertFalse( \Softcatala\Content\LlmsEndpoint::matches_request( 'llms.txt/extra', 'summary' ) );
		$this->assertFalse( \Softcatala\Content\LlmsEndpoint::matches_request( '', 'full' ) );
		$this->assertFalse( \Softcatala\Content\LlmsEndpoint::matches_request( 'llms.txt', 'unknown' ) );
	}

	public function test_summary_follows_the_llms_txt_structure() {
		$document = \Softcatala\Content\LlmsEndpoint::document( 'summary' );

		$this->assertStringStartsWith( "# Softcatalà\n\n> ", $document );
		$this->assertStringContainsString( "\n## Eines lingüístiques\n\n- [Traductor]", $document );
		$this->assertStringContainsString( home_url( '/llms-full.txt' ), $document );
		$this->assertStringNotContainsString( 'https://www.softcatala.org', $document );
		$this->assertSame( "\n", substr( $document, -1 ) );
	}

	public function test_full_document_is_curated_and_bounded() {
		$document = \Softcatala\Content\LlmsEndpoint::document( 'full' );

		$this->assertStringStartsWith( '# Softcatalà — context ampliat', $document );
		$this->assertStringContainsString( 'No forma part de l\'especificació llms.txt v2', $document );
		$this->assertStringContainsString( home_url( '/dades-obertes/' ), $document );
		$this->assertLessThan( 512 * 1024, strlen( $document ) );
	}

	public function test_theme_disables_the_competing_yoast_generator() {
		$options = apply_filters(
			'option_wpseo',
			array(
				'enable_llms_txt' => true,
				'other_setting'   => true,
			)
		);

		$this->assertFalse( $options['enable_llms_txt'] );
		$this->assertTrue( $options['other_setting'] );
	}

	public function test_conditional_request_accepts_wordpress_slashed_etag() {
		$method = new ReflectionMethod( \Softcatala\Content\LlmsEndpoint::class, 'is_not_modified' );
		$method->setAccessible( true );

		$original = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
		$_SERVER['HTTP_IF_NONE_MATCH'] = addslashes( '"document-etag"' );

		$this->assertTrue( $method->invoke( null, '"document-etag"', time() ) );

		if ( null === $original ) {
			unset( $_SERVER['HTTP_IF_NONE_MATCH'] );
		} else {
			$_SERVER['HTTP_IF_NONE_MATCH'] = $original;
		}
	}
}
