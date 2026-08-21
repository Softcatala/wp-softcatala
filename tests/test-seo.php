<?php
/**
 * Regression coverage for site-wide SEO behaviour.
 */
class SeoTest extends SCTests {

	public function test_context_filterer_synchronises_yoast_metadata() {
		$canonical = home_url( '/diccionari-de-sinonims/paraula/acorar/' );
		$filterer = new SC_ContextFilterer();
		$setup_filters = new ReflectionMethod( SC_ContextFilterer::class, 'setup_filters' );
		$setup_filters->setAccessible( true );
		$setup_filters->invoke(
			$filterer,
			array(
				'title'            => 'acorar - Diccionari de sinònims | Softcatalà',
				'description'      => 'Sinònims de «acorar» en català.',
				'canonical'        => $canonical,
				'breadcrumb_title' => 'Diccionari de sinònims: «acorar»',
				'breadcrumb_parent_url' => home_url( '/diccionari-de-sinonims/' ),
			)
		);

		$this->assertSame( $canonical, apply_filters( 'wpseo_canonical', home_url( '/diccionari-de-sinonims/' ) ) );
		$this->assertSame( $canonical, apply_filters( 'wpseo_opengraph_url', home_url( '/diccionari-de-sinonims/' ) ) );
		$this->assertSame( 'Sinònims de «acorar» en català.', apply_filters( 'wpseo_opengraph_desc', '' ) );
		$this->assertSame( 'Sinònims de «acorar» en català.', apply_filters( 'wpseo_twitter_description', '' ) );

		$webpage = apply_filters(
			'wpseo_schema_webpage',
			array(
				'@id'             => home_url( '/diccionari-de-sinonims/#webpage' ),
				'url'             => home_url( '/diccionari-de-sinonims/' ),
				'potentialAction' => array(
					array( '@type' => 'ReadAction', 'target' => array( home_url( '/diccionari-de-sinonims/' ) ) ),
				),
			)
		);

		$this->assertSame( $canonical . '#webpage', $webpage['@id'] );
		$this->assertSame( $canonical, $webpage['url'] );
		$this->assertSame( array( $canonical ), $webpage['potentialAction'][0]['target'] );
		$this->assertSame( $canonical . '#breadcrumb', $webpage['breadcrumb']['@id'] );

		$breadcrumbs = apply_filters(
			'wpseo_schema_breadcrumb',
			array(
				'@type' => 'BreadcrumbList',
				'itemListElement' => array(
					array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Inici', 'item' => home_url( '/' ) ),
					array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Diccionari de sinònims' ),
				),
			)
		);

		$this->assertSame( $canonical . '#breadcrumb', $breadcrumbs['@id'] );
		$this->assertCount( 3, $breadcrumbs['itemListElement'] );
		$this->assertSame( home_url( '/diccionari-de-sinonims/' ), $breadcrumbs['itemListElement'][1]['item'] );
		$this->assertSame( $canonical, $breadcrumbs['itemListElement'][2]['item'] );
		$this->assertSame( 3, $breadcrumbs['itemListElement'][2]['position'] );

		remove_filter( 'wpseo_title', array( $filterer, 'change_title' ) );
		remove_filter( 'wpseo_opengraph_title', array( $filterer, 'change_title' ) );
		remove_filter( 'wpseo_twitter_title', array( $filterer, 'change_title' ) );
		remove_filter( 'wpseo_metadesc', array( $filterer, 'change_description' ) );
		remove_filter( 'wpseo_opengraph_desc', array( $filterer, 'change_description' ) );
		remove_filter( 'wpseo_twitter_description', array( $filterer, 'change_description' ) );
		remove_filter( 'wpseo_canonical', array( $filterer, 'change_canonical' ) );
		remove_filter( 'wpseo_opengraph_url', array( $filterer, 'change_canonical' ) );
		remove_filter( 'wpseo_schema_webpage', array( $filterer, 'change_webpage_schema' ) );
		remove_filter( 'wpseo_schema_breadcrumb', array( $filterer, 'change_breadcrumb_schema' ) );
	}

	public function test_empty_description_uses_existing_editorial_content() {
		$post_id = self::factory()->post->create(
			array(
				'post_title'   => 'Notícia amb contingut',
				'post_content' => '<p>Aquest és el resum factual de la notícia que ja existeix al contingut.</p>',
			)
		);
		wp_update_post( array( 'ID' => $post_id, 'post_excerpt' => '' ) );

		$this->go_to( get_permalink( $post_id ) );

		$this->assertSame(
			'Aquest és el resum factual de la notícia que ja existeix al contingut.',
			apply_filters( 'wpseo_metadesc', '' )
		);
		$this->assertSame( 'Descripció editorial', apply_filters( 'wpseo_metadesc', 'Descripció editorial' ) );
	}

	public function test_taxonomy_metadata_has_specific_heading_and_description() {
		$term = self::factory()->term->create_and_get(
			array(
				'taxonomy' => 'category',
				'name'     => 'Llengua catalana',
			)
		);

		$metadata = SC_Seo::term_metadata( $term );

		$this->assertSame( 'Notícies sobre «Llengua catalana»', $metadata['content_title'] );
		$this->assertStringContainsString( 'Llengua catalana', $metadata['description'] );
	}

	public function test_program_and_project_titles_express_different_intents() {
		$program_id = self::factory()->post->create(
			array(
				'post_type'  => 'programa',
				'post_title' => 'GNOME',
			)
		);
		$this->go_to( get_permalink( $program_id ) );
		$this->assertSame( 'GNOME en català: baixada i informació | ' . get_bloginfo( 'name' ), apply_filters( 'wpseo_title', 'GNOME | Softcatalà' ) );

		$project_id = self::factory()->post->create(
			array(
				'post_type'  => 'projecte',
				'post_title' => 'GNOME',
			)
		);
		$this->go_to( get_permalink( $project_id ) );
		$this->assertSame( 'Projecte «GNOME»: col·laboreu-hi | ' . get_bloginfo( 'name' ), apply_filters( 'wpseo_title', 'GNOME | Softcatalà' ) );

		update_post_meta( $project_id, '_yoast_wpseo_title', 'Títol editorial' );
		$this->assertSame( 'Títol editorial renderitzat', apply_filters( 'wpseo_title', 'Títol editorial renderitzat' ) );
	}

	public function test_slider_is_admin_content_but_not_publicly_queryable() {
		$post_type = get_post_type_object( 'slider' );

		$this->assertNotNull( $post_type );
		$this->assertTrue( $post_type->show_ui );
		$this->assertFalse( $post_type->public );
		$this->assertFalse( $post_type->publicly_queryable );
		$this->assertFalse( $post_type->show_in_nav_menus );
	}

	public function test_dynamic_sitemap_omits_fake_dates_and_normalises_words() {
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

	public function test_dynamic_sitemap_uses_last_valid_copy_when_api_fails() {
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

		delete_transient( 'sc_sitemap_' . md5( 'prova-cache:A' ) . '_fresh' );
		delete_transient( 'sc_sitemap_' . md5( 'prova-cache:A' ) . '_stale' );

		$this->assertSame( array( 'acorar' ), $sitemap->words( 'A' ) );
		delete_transient( 'sc_sitemap_' . md5( 'prova-cache:A' ) . '_fresh' );
		$client->response = array( 'error' => true, 'code' => 500, 'result' => '' );
		$this->assertSame( array( 'acorar' ), $sitemap->words( 'A' ) );
	}

	public function test_custom_rewrites_are_exact_and_pages_are_numeric() {
		$rules = sc_custom__rewrite_rules( array() );

		foreach ( array_keys( $rules ) as $pattern ) {
			$this->assertStringStartsWith( '^', $pattern );
			$this->assertStringEndsWith( '$', $pattern );
		}

		$this->assertArrayHasKey( '^conjugador-de-verbs/verb/([^/]+)/?$', $rules );
		$this->assertArrayHasKey( '^programes/p/([^/]+)/pagina/([0-9]+)/?$', $rules );
	}

	public function test_archived_program_query_does_not_exclude_archived_programs() {
		$reflection = new ReflectionMethod( \Softcatala\Providers\Programes::class, 'get_query_args' );
		$reflection->setAccessible( true );
		$args = $reflection->invoke( null, array( 'classificacio' => 'arxivat' ) );

		$this->assertSame( 'arxivat', $args['tax_query'][0]['terms'] );
		$this->assertArrayNotHasKey( 'operator', $args['tax_query'][0] );
	}

	public function test_dictionary_dto_keeps_result_separate_from_detected_language() {
		$result = (object) array( 'results' => array() );
		$dto = new SC_Diccionari_EngCatResult( 200, '', 'house', '', '', '', $result, 'eng' );

		$this->assertSame( $result, $dto->result );
		$this->assertSame( 'eng', $dto->detected_language );
	}
}
