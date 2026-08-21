<?php
/**
 * Regression coverage for metadata overrides on dynamic pages.
 */
require_once( 'sc_tests.php' );

class ContextFiltererSeoTest extends SCTests {

	public function test_metadata_overrides_stay_in_sync() {
		$canonical = home_url( '/diccionari-de-sinonims/paraula/acorar/' );
		$filterer = new SC_ContextFilterer();
		$setup_filters = new ReflectionMethod( SC_ContextFilterer::class, 'setup_filters' );
		$setup_filters->setAccessible( true );
		$setup_filters->invoke(
			$filterer,
			array(
				'title'       => 'acorar - Diccionari de sinònims | Softcatalà',
				'description' => 'Sinònims de «acorar» en català.',
				'canonical'   => $canonical,
			)
		);

		$this->assertSame( $canonical, apply_filters( 'wpseo_canonical', home_url( '/diccionari-de-sinonims/' ) ) );
		$this->assertSame( $canonical, apply_filters( 'wpseo_opengraph_url', home_url( '/diccionari-de-sinonims/' ) ) );
		$this->assertSame( 'acorar - Diccionari de sinònims | Softcatalà', apply_filters( 'wpseo_twitter_title', '' ) );
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

		remove_filter( 'wpseo_title', array( $filterer, 'change_title' ) );
		remove_filter( 'wpseo_opengraph_title', array( $filterer, 'change_title' ) );
		remove_filter( 'wpseo_twitter_title', array( $filterer, 'change_title' ) );
		remove_filter( 'wpseo_metadesc', array( $filterer, 'change_description' ) );
		remove_filter( 'wpseo_opengraph_desc', array( $filterer, 'change_description' ) );
		remove_filter( 'wpseo_twitter_description', array( $filterer, 'change_description' ) );
		remove_filter( 'wpseo_canonical', array( $filterer, 'change_canonical' ) );
		remove_filter( 'wpseo_opengraph_url', array( $filterer, 'change_canonical' ) );
		remove_filter( 'wpseo_schema_webpage', array( $filterer, 'change_webpage_schema' ) );
	}
}
