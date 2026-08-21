<?php
/**
 * Regression coverage for dictionary sitemap generation.
 */
require_once( 'sc_tests.php' );

class DictionarySitemapTest extends SCTests {

	public function test_sitemap_omits_fake_dates_and_normalises_words() {
		$sitemap = new class() extends \Softcatala\Sitemaps\DictionarySitemap {
			protected function slug() { return 'prova'; }
			protected function keys() { return array( 'A' ); }
			protected function api_url( $key ) { return ''; }
			protected function word_url( $key, $word ) { return '/prova/' . $word . '/'; }
			protected function extract_words( $raw_json ) { return array(); }
			public function normalise( $words ) { return $this->normalise_words( $words ); }
		};

		$this->assertStringNotContainsString( '<lastmod>', $sitemap->sitemap_index() );
		$this->assertSame(
			array( 'acorar', 'afligir' ),
			$sitemap->normalise( array( 'acorar', 'AcOrAr', 'afligir', 'mot/amb/barra', '' ) )
		);
	}

	public function test_sitemap_uses_last_valid_copy_when_api_fails() {
		$client = new class() {
			public $response = array(
				'error'  => false,
				'code'   => 200,
				'result' => '{"words":["acorar","AcOrAr"]}',
			);
			public function get( $url, $use_api_key ) { return $this->response; }
		};

		$sitemap = new class( $client ) extends \Softcatala\Sitemaps\DictionarySitemap {
			protected function slug() { return 'prova-cache'; }
			protected function keys() { return array( 'A' ); }
			protected function api_url( $key ) { return 'https://example.invalid/' . $key; }
			protected function word_url( $key, $word ) { return '/prova/' . $word . '/'; }
			protected function extract_words( $raw_json ) {
				$data = json_decode( $raw_json, true );
				return $data['words'] ?? array();
			}
			public function words( $key ) { return $this->words_for_key( $key ); }
		};

		$fresh_key = 'sc_sitemap_' . md5( 'prova-cache:A' ) . '_fresh';
		$stale_key = 'sc_sitemap_' . md5( 'prova-cache:A' ) . '_stale';
		delete_transient( $fresh_key );
		delete_transient( $stale_key );

		$this->assertSame( array( 'acorar' ), $sitemap->words( 'A' ) );
		delete_transient( $fresh_key );
		$client->response = array( 'error' => true, 'code' => 500, 'result' => '' );
		$this->assertSame( array( 'acorar' ), $sitemap->words( 'A' ) );

		delete_transient( $fresh_key );
		delete_transient( $stale_key );
	}
}
