<?php
/**
 * Class SC_AdsTest
 *
 * @package Softcatala
 */

use Softcatala\Ads\Sections;
use Softcatala\Ads\ZoneResolver;

require_once( 'sc_tests.php' );

/**
 * Revive ad slots: section detection and per-slot zone resolution
 */
class SC_AdsTest extends SCTests {

	function tear_down() {
		unset( $_GET[ ZoneResolver::QUERY_PARAM ] );
		parent::tear_down();
	}

	private function page_with_template( $template ) {
		$id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $id, '_wp_page_template', $template );
		return $id;
	}

	private function resolve( $section, $post_id = 0, $query = '' ) {
		return ( new ZoneResolver() )->resolve( $section, $post_id, $query )['slots'];
	}

	/**
	 * Pages map to sections by template, programes by post type, anything else to default
	 */
	function test_sections_map_templates_and_post_types() {
		$sections = new Sections();

		$this->assertSame( 'corrector', $sections->for_post( get_post( $this->page_with_template( 'corrector-antic.php' ) ) ) );
		$this->assertSame( 'diccionaris', $sections->for_post( get_post( $this->page_with_template( 'diccionari-engcat.php' ) ) ) );
		$this->assertSame( 'recursos', $sections->for_post( get_post( $this->page_with_template( 'conjugador.php' ) ) ) );
		$this->assertSame( 'default', $sections->for_post( get_post( $this->page_with_template( 'page-no-sidebar.php' ) ) ) );
		$this->assertSame( 'default', $sections->for_post( get_post( $this->factory->post->create() ) ) );
		$this->assertSame( 'default', $sections->for_post( null ) );

		$programa = $this->factory->post->create( array( 'post_type' => 'programa' ) );
		$this->assertSame( 'programes', $sections->for_post( get_post( $programa ) ) );
	}

	/**
	 * With nothing saved, the seeds reproduce today's zones
	 */
	function test_seed_values_apply_until_saved() {
		$slots = $this->resolve( 'corrector' );

		$this->assertSame( array( 'zone' => 36, 'source' => 'section' ), $slots['sidebar_top'] );
		$this->assertSame( array( 'zone' => 13, 'source' => 'default' ), $slots['header_1'] );
		$this->assertSame( array( 'zone' => 17, 'source' => 'default' ), $slots['header_2'] );
		$this->assertSame( array( 'zone' => null, 'source' => 'default' ), $slots['sidebar_bottom'] );

		$this->assertSame( 38, $this->resolve( 'traductor' )['sidebar_top']['zone'] );
		$this->assertSame( 40, $this->resolve( 'diccionaris' )['sidebar_top']['zone'] );
		$this->assertNull( $this->resolve( 'recursos' )['sidebar_top']['zone'] );
	}

	/**
	 * Empty inherits from default, 0 switches the slot off, a number wins
	 */
	function test_saved_section_values() {
		update_option( ZoneResolver::option_name( 'default', 'sidebar_top' ), '50' );

		update_option( ZoneResolver::option_name( 'corrector', 'sidebar_top' ), '0' );
		$this->assertSame( array( 'zone' => null, 'source' => 'section' ), $this->resolve( 'corrector' )['sidebar_top'] );

		update_option( ZoneResolver::option_name( 'corrector', 'sidebar_top' ), '' );
		$this->assertSame( array( 'zone' => 50, 'source' => 'default' ), $this->resolve( 'corrector' )['sidebar_top'] );

		update_option( ZoneResolver::option_name( 'corrector', 'sidebar_top' ), '61' );
		$this->assertSame( array( 'zone' => 61, 'source' => 'section' ), $this->resolve( 'corrector' )['sidebar_top'] );

		delete_option( ZoneResolver::option_name( 'corrector', 'sidebar_top' ) );
		$this->assertSame( 36, $this->resolve( 'corrector' )['sidebar_top']['zone'] );
	}

	/**
	 * The default section's own 0 or empty means no ad
	 */
	function test_default_section_can_switch_slot_off() {
		update_option( ZoneResolver::option_name( 'default', 'header_1' ), '0' );
		$this->assertSame( array( 'zone' => null, 'source' => 'default' ), $this->resolve( 'home' )['header_1'] );

		update_option( ZoneResolver::option_name( 'default', 'header_1' ), '' );
		$this->assertNull( $this->resolve( 'default' )['header_1']['zone'] );
	}

	/**
	 * A page override beats its section; 0 hides the slot
	 */
	function test_page_override() {
		$page = $this->page_with_template( 'corrector.php' );

		update_post_meta( $page, ZoneResolver::meta_key( 'sidebar_top' ), '77' );
		$this->assertSame( array( 'zone' => 77, 'source' => 'page' ), $this->resolve( 'corrector', $page )['sidebar_top'] );

		update_post_meta( $page, ZoneResolver::meta_key( 'sidebar_top' ), '0' );
		$this->assertSame( array( 'zone' => null, 'source' => 'page' ), $this->resolve( 'corrector', $page )['sidebar_top'] );

		update_post_meta( $page, ZoneResolver::meta_key( 'sidebar_top' ), '' );
		$this->assertSame( array( 'zone' => 36, 'source' => 'section' ), $this->resolve( 'corrector', $page )['sidebar_top'] );
	}

	/**
	 * ?ads= beats everything for the request, several pairs at once
	 */
	function test_query_forces_and_hides_slots() {
		$page = $this->page_with_template( 'corrector.php' );
		update_post_meta( $page, ZoneResolver::meta_key( 'sidebar_top' ), '77' );

		$slots = $this->resolve( 'corrector', $page, 'sidebar_top:42,header_1:99' );
		$this->assertSame( array( 'zone' => 42, 'source' => 'query' ), $slots['sidebar_top'] );
		$this->assertSame( array( 'zone' => 99, 'source' => 'query' ), $slots['header_1'] );

		$slots = $this->resolve( 'corrector', $page, '-sidebar_top' );
		$this->assertSame( array( 'zone' => null, 'source' => 'query' ), $slots['sidebar_top'] );
	}

	/**
	 * debug combines with the other tokens
	 */
	function test_query_debug() {
		$resolver = new ZoneResolver();

		$this->assertFalse( $resolver->resolve( 'corrector', 0, '' )['debug'] );

		$resolved = $resolver->resolve( 'corrector', 0, 'debug,sidebar_top:42' );
		$this->assertTrue( $resolved['debug'] );
		$this->assertSame( 42, $resolved['slots']['sidebar_top']['zone'] );
	}

	/**
	 * Unknown slots, non-numeric or non-positive zones and markup are ignored
	 */
	function test_query_ignores_malformed_tokens() {
		$resolver = new ZoneResolver();

		foreach ( array( 'bogus:5', 'sidebar_top:abc', 'sidebar_top:-3', 'sidebar_top:', 'sidebar_top:0', '-bogus', '<script>', ',,', 'DEBUG' ) as $raw ) {
			$this->assertSame(
				array( 'debug' => false, 'zones' => array(), 'hidden' => array() ),
				$resolver->parse_query( $raw ),
				$raw
			);
		}
	}

	/**
	 * The request reader only accepts a string ?ads=
	 */
	function test_read_query() {
		$this->assertSame( '', ZoneResolver::read_query() );

		$_GET['ads'] = array( 'sidebar_top:42' );
		$this->assertSame( '', ZoneResolver::read_query() );

		$_GET['ads'] = 'sidebar_top:42';
		$this->assertSame( 'sidebar_top:42', ZoneResolver::read_query() );
		$this->assertTrue( ZoneResolver::has_query_overrides() );

		$_GET['ads'] = 'bogus:1';
		$this->assertFalse( ZoneResolver::has_query_overrides() );
	}
}
