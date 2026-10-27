<?php
/**
 * Class SC_CatalanitzadorTest
 *
 * @package Softcatala
 */

use Softcatala\Providers\CatalanitzadorStats;

require_once( 'sc_tests.php' );

/**
 * Catalanitzador programa sidebar (stats box and hashtag button) and its stats provider
 */
class SC_CatalanitzadorTest extends SCTests {

	private $stats_requests = 0;

	function tear_down() {
		unset( $_GET[ SC_Settings::OVERRIDE_PARAM ] );
		SC_Settings::get_instance()->reload();
		parent::tear_down();
	}

	private function catalanitzador_sidebar( $slug = 'catalanitzador', $flag = 'ad_slots', $sessions = 1234567 ) {
		$id = $this->factory->post->create( array( 'post_type' => 'programa', 'post_name' => $slug ) );
		$this->go_to( get_permalink( $id ) );
		// go_to() resets $_GET, so the flag override goes in afterwards.
		$_GET[ SC_Settings::OVERRIDE_PARAM ] = $flag;
		set_transient( CatalanitzadorStats::TRANSIENT_NAME, $sessions, HOUR_IN_SECONDS );

		return sc_programa_sidebar( array( 'sidebar_elements' => array( 'baixades.twig', 'links.twig' ) ), get_post( $id ) );
	}

	/**
	 * Answers every HTTP request with the given status and body, counting requests made
	 */
	private function stub_stats_source( $code, $body ) {
		$this->stats_requests = 0;
		add_filter(
			'pre_http_request',
			function () use ( $code, $body ) {
				$this->stats_requests++;
				return array(
					'headers'  => array(),
					'body'     => $body,
					'response' => array( 'code' => $code, 'message' => '' ),
					'cookies'  => array(),
				);
			}
		);
	}


	/**
	 * With ad_slots on, the Catalanitzador programa gets its stats first and the hashtag button last
	 */
	function test_catalanitzador_sidebar_content() {
		$context = $this->catalanitzador_sidebar();
		$this->assertSame(
			array( 'programa/catalanitzador-stats.twig', 'baixades.twig', 'links.twig', 'programa/catalanitzador-hashtag.twig' ),
			$context['sidebar_elements']
		);
		$this->assertSame( '1.234.567', $context['catalanitzador_sessions'] );

		$html = \Timber\Timber::compile( 'programa/catalanitzador-stats.twig', $context );
		$this->assertStringContainsString( '<span class="catalanitzador_sessions">1.234.567</span>', $html );
		$this->assertStringContainsString( 'catalanitzador-banner-stats.png', $html );
		$this->assertStringContainsString( 'href="/catalanitzador/stats/"', $html );

		$html = \Timber\Timber::compile( 'programa/catalanitzador-hashtag.twig', array() );
		$this->assertStringContainsString( 'button_hashtag=catalanitzador', $html );

		$this->assertSame( array( 'baixades.twig', 'links.twig' ), $this->catalanitzador_sidebar( 'gimp' )['sidebar_elements'] );
	}

	/**
	 * With ad_slots off, or on a programa subpage, the sidebar is unchanged
	 */
	function test_catalanitzador_sidebar_needs_flag_and_programa_page() {
		$this->assertSame( array( 'baixades.twig', 'links.twig' ), $this->catalanitzador_sidebar( 'catalanitzador', '' )['sidebar_elements'] );

		$programa = $this->factory->post->create( array( 'post_type' => 'programa', 'post_name' => 'catalanitzador' ) );
		$subpage  = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $subpage ) );
		$_GET[ SC_Settings::OVERRIDE_PARAM ] = 'ad_slots';

		$context = sc_programa_sidebar( array( 'sidebar_elements' => array( 'links.twig' ) ), get_post( $programa ) );
		$this->assertSame( array( 'links.twig' ), $context['sidebar_elements'] );
	}

	/**
	 * Without a count the stats box is left out, but the hashtag button stays
	 */
	function test_catalanitzador_sidebar_without_count() {
		$this->stub_stats_source( 502, 'Bad Gateway' );

		$context = $this->catalanitzador_sidebar( 'catalanitzador', 'ad_slots', CatalanitzadorStats::UNAVAILABLE );
		$this->assertSame( array( 'baixades.twig', 'links.twig', 'programa/catalanitzador-hashtag.twig' ), $context['sidebar_elements'] );
		$this->assertArrayNotHasKey( 'catalanitzador_sessions', $context );
	}

	/**
	 * A successful fetch is cached for a day
	 */
	function test_stats_count_fetched_and_cached() {
		delete_transient( CatalanitzadorStats::TRANSIENT_NAME );
		$this->stub_stats_source( 200, '98765' );

		$this->assertSame( 98765, CatalanitzadorStats::sessions() );
		$this->assertSame( 98765, CatalanitzadorStats::sessions() );
		$this->assertSame( 1, $this->stats_requests );
	}

	/**
	 * A failing source is not asked again on every page view
	 */
	function test_stats_source_failure_is_cached() {
		delete_transient( CatalanitzadorStats::TRANSIENT_NAME );
		$this->stub_stats_source( 502, 'Bad Gateway' );

		$this->assertFalse( CatalanitzadorStats::sessions() );
		$this->assertFalse( CatalanitzadorStats::sessions() );
		$this->assertSame( 1, $this->stats_requests );

		delete_transient( CatalanitzadorStats::TRANSIENT_NAME );
		$this->stub_stats_source( 200, 'not a number' );
		$this->assertFalse( CatalanitzadorStats::sessions() );
	}
}
