<?php
/**
 * Class SC_AdsTest
 *
 * @package Softcatala
 */

use Softcatala\Ads\HeadTags;
use Softcatala\Ads\Renderer;
use Softcatala\Ads\Sections;
use Softcatala\Ads\SettingsPage;
use Softcatala\Ads\ZoneResolver;

require_once( 'sc_tests.php' );

/**
 * Stands in for a WooSidebars class hooked on get_header.
 */
class Woo_Sidebars_Test_Double {
	public function replace_sidebars() {}
}

// WP Super Cache is not installed in the test suite; stand in for its clear-all API and count calls.
if ( ! function_exists( 'wp_cache_clear_cache' ) ) {
	function wp_cache_clear_cache() {
		$GLOBALS['sc_test_page_cache_clears'] = ( $GLOBALS['sc_test_page_cache_clears'] ?? 0 ) + 1;
	}
}

/**
 * Revive ad slots: section detection and per-slot zone resolution
 */
class SC_AdsTest extends SCTests {

	function tear_down() {
		unset( $_GET[ ZoneResolver::QUERY_PARAM ], $_GET[ SC_Settings::OVERRIDE_PARAM ] );
		wp_dequeue_script( HeadTags::CLICKIO_HANDLE );
		wp_deregister_script( HeadTags::CLICKIO_HANDLE );
		SC_Settings::get_instance()->reload();
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

	/**
	 * The Publicitat options page has one zone field per section and slot, pre-filled with the seed
	 */
	function test_options_field_group_covers_every_section_and_slot() {
		$config = ZoneResolver::load_config();
		$fields = acf_get_fields( SettingsPage::OPTIONS_GROUP );
		$zones  = array();

		foreach ( $fields as $field ) {
			if ( 'number' === $field['type'] ) {
				$zones[ $field['name'] ] = $field['default_value'];
			}
		}

		$this->assertCount( count( $config['sections'] ) * count( $config['slots'] ), $zones );
		$this->assertSame( 36, $zones[ ZoneResolver::field_name( 'corrector', 'sidebar_top' ) ] );
		$this->assertSame( 13, $zones[ ZoneResolver::field_name( 'default', 'header_1' ) ] );
		$this->assertSame( '', $zones[ ZoneResolver::field_name( 'recursos', 'sidebar_top' ) ] );
	}

	/**
	 * Pages and programes get one optional override field per slot, stored under the resolver's meta key
	 */
	function test_page_field_group_targets_pages_and_programes() {
		$group = acf_get_field_group( SettingsPage::PAGE_GROUP );

		$this->assertSame( array( 'page', 'programa' ), array_map( fn( $rule ) => $rule[0]['value'], $group['location'] ) );
		$this->assertSame(
			array_map( array( ZoneResolver::class, 'meta_key' ), array_keys( ZoneResolver::load_config()['slots'] ) ),
			wp_list_pluck( acf_get_fields( SettingsPage::PAGE_GROUP ), 'name' )
		);
	}

	/**
	 * Free ACF (as in CI) has no options pages; registering must not fail
	 */
	function test_options_page_registration_is_guarded() {
		( new SettingsPage() )->register_options_page();

		$this->assertTrue( true );
	}

	/**
	 * `ads` context as add_user_nav_info_to_context builds it, for a given section and query
	 */
	private function ads_context( $enabled = true, $section = 'corrector', $query = '' ) {
		$config = ZoneResolver::load_config();
		$ads    = array(
			'enabled'      => $enabled,
			'debug'        => false,
			'slots'        => array(),
			'revive_id'    => $config['revive_id'],
			'delivery_url' => $config['delivery_url'],
		);

		return $enabled ? array_merge( $ads, ( new ZoneResolver() )->resolve( $section, 0, $query ) ) : $ads;
	}

	private function render_slot( $slot, $ads ) {
		Renderer::reset();
		return trim( \Timber\Timber::compile( 'ads/slot.twig', array( 'slot' => $slot, 'ads' => $ads ) ) );
	}

	/**
	 * A page shaped like the recursos tools: header banners plus the recursos sidebar
	 */
	private function render_page( $context ) {
		Renderer::reset();
		$template = '{% extends "base.twig" %}{% block content %}{% include "sidebar_recursos.twig" %}{% endblock %}';

		return \Timber\Timber::compile_string( $template, $context );
	}

	/**
	 * A slot with a zone renders its <ins> with the configured Revive id
	 */
	function test_slot_renders_zone() {
		$html = $this->render_slot( 'sidebar_top', $this->ads_context() );

		$this->assertSame( 1, substr_count( $html, '<ins data-revive-zoneid="36" data-revive-id="eb4534b21d1ac483f6974b8de847dbe7"></ins>' ) );
		$this->assertStringNotContainsString( 'asyncjs.php', $html );
		$this->assertTrue( Renderer::script_needed() );
	}

	/**
	 * An empty slot renders nothing, and leaves the script unneeded
	 */
	function test_empty_slot_renders_nothing() {
		$this->assertSame( '', $this->render_slot( 'sidebar_bottom', $this->ads_context() ) );
		$this->assertFalse( Renderer::script_needed() );

		$this->assertSame( '', $this->render_slot( 'sidebar_top', $this->ads_context( false ) ) );
	}

	/**
	 * Debug outlines every slot, labelled with slot, zone (or "cap") and source
	 */
	function test_debug_labels_slots() {
		$ads = $this->ads_context( true, 'corrector', 'debug' );

		$this->assertStringContainsString( 'sidebar_bottom · cap · default', $this->render_slot( 'sidebar_bottom', $ads ) );

		$filled = $this->render_slot( 'sidebar_top', $this->ads_context( true, 'corrector', 'debug,sidebar_top:42' ) );
		$this->assertStringContainsString( 'sidebar_top · 42 · query', $filled );
		$this->assertStringContainsString( 'data-revive-zoneid="42"', $filled );
	}

	/**
	 * Header and sidebar slots together load the Revive script exactly once
	 */
	function test_page_loads_revive_script_once() {
		$html = $this->render_page( array( 'ads' => $this->ads_context(), 'ads_container' => true ) );

		$this->assertStringContainsString( 'data-revive-zoneid="13"', $html );
		$this->assertStringContainsString( 'data-revive-zoneid="17"', $html );
		$this->assertStringContainsString( 'data-revive-zoneid="36"', $html );
		$this->assertSame( 1, substr_count( $html, 'asyncjs.php' ) );
	}

	/**
	 * No rendered slot, no script: pages without ads_container skip the header zones the default section resolves
	 */
	function test_page_without_rendered_slots_has_no_script() {
		$html = $this->render_page( array( 'ads' => $this->ads_context( true, 'recursos' ), 'ads_container' => false ) );

		$this->assertStringNotContainsString( 'data-revive-zoneid', $html );
		$this->assertSame( 0, substr_count( $html, 'asyncjs.php' ) );
	}

	/**
	 * With ad_slots off the page is today's: hard-coded header zones, no slots, no debug
	 */
	function test_flag_off_keeps_legacy_markup() {
		$_GET['ads'] = 'debug,sidebar_top:42';
		$ads         = Renderer::context( false );
		$this->assertSame( array(), $ads['slots'] );
		$this->assertFalse( $ads['debug'] );

		$html = $this->render_page( array( 'ads' => $ads, 'ads_container' => true ) );

		$this->assertStringContainsString( 'data-revive-zoneid="13"', $html );
		$this->assertStringContainsString( 'data-revive-zoneid="17"', $html );
		$this->assertStringNotContainsString( 'data-revive-zoneid="36"', $html );
		$this->assertStringNotContainsString( 'sc-ad-debug', $html );
		$this->assertSame( 2, substr_count( $html, 'asyncjs.php' ) );
	}

	/**
	 * With ad_slots on, the request context resolves the current section and applies ?ads=
	 */
	function test_request_context_resolves_current_request() {
		$page = $this->page_with_template( 'traductor.php' );
		$this->go_to( get_permalink( $page ) );
		$_GET['ads'] = 'debug,header_2:55';

		$ads = Renderer::context( true );

		$this->assertTrue( $ads['enabled'] );
		$this->assertTrue( $ads['debug'] );
		$this->assertSame( array( 'zone' => 38, 'source' => 'section' ), $ads['slots']['sidebar_top'] );
		$this->assertSame( array( 'zone' => 55, 'source' => 'query' ), $ads['slots']['header_2'] );
	}

	/**
	 * A valid ?ads= keeps the page out of the page cache; an ignored one does not
	 */
	function test_query_overrides_skip_page_cache() {
		global $sc_site;

		$this->assertNotFalse( has_action( 'template_redirect', array( $sc_site, 'sc_skip_cache_for_request_overrides' ) ) );

		$_GET['ads'] = 'bogus:1';
		$this->assertFalse( $sc_site->has_request_overrides() );

		$_GET['ads'] = 'sidebar_top:42';
		$this->assertTrue( $sc_site->has_request_overrides() );

		unset( $_GET['ads'] );
		$_GET[ SC_Settings::OVERRIDE_PARAM ] = 'clickio_cmp';
		$this->assertTrue( $sc_site->has_request_overrides() );
		unset( $_GET[ SC_Settings::OVERRIDE_PARAM ] );
	}

	/**
	 * ad_slots is an overridable flag that starts off
	 */
	function test_ad_slots_flag_is_overridable_and_off() {
		$settings = SC_Settings::get_instance();
		$settings->reload();

		$this->assertTrue( $settings->get_flags()[ SC_Settings::SETTINGS_AD_SLOTS ]['overridable'] );
		$this->assertFalse( $settings->get_setting( SC_Settings::SETTINGS_AD_SLOTS ) );
	}

	/**
	 * clickio_ads is an overridable flag that starts off
	 */
	function test_clickio_ads_flag_is_overridable_and_off() {
		$settings = SC_Settings::get_instance();
		$settings->reload();

		$this->assertTrue( $settings->get_flags()[ SC_Settings::SETTINGS_CLICKIO_ADS ]['overridable'] );
		$this->assertFalse( $settings->get_setting( SC_Settings::SETTINGS_CLICKIO_ADS ) );
	}

	/**
	 * Rendering a page with the ad banner enqueues the Clickio tag as an async head script
	 */
	function test_clickio_tag_loads_in_head_on_ad_pages() {
		$this->assertNotFalse( has_filter( 'timber/compile/data', array( HeadTags::class, 'enqueue' ) ) );

		// Page templates call Timber::render(), which fires timber/render/data rather than timber/compile/data.
		$_GET[ SC_Settings::OVERRIDE_PARAM ] = SC_Settings::SETTINGS_CLICKIO_ADS;
		ob_start();
		\Timber\Timber::render( 'ads/slot.twig', array( 'ads_container' => true ) );
		ob_end_clean();

		$this->assertTrue( wp_script_is( HeadTags::CLICKIO_HANDLE, 'enqueued' ) );

		$html = get_echo( 'wp_print_head_scripts' );
		$this->assertMatchesRegularExpression( '#<script async [^>]*src="//s\.clickiocdn\.com/t/250605/360_light\.js"#', $html );
		$this->assertStringNotContainsString( '360_light.js?ver=', $html );
	}

	/**
	 * The Clickio tag stays off pages without ads, and off everywhere while the flag is off
	 */
	function test_clickio_tag_needs_ad_page_and_flag() {
		$_GET[ SC_Settings::OVERRIDE_PARAM ] = SC_Settings::SETTINGS_CLICKIO_ADS;
		HeadTags::enqueue( array() );
		$this->assertFalse( wp_script_is( HeadTags::CLICKIO_HANDLE, 'enqueued' ) );

		unset( $_GET[ SC_Settings::OVERRIDE_PARAM ] );
		HeadTags::enqueue( array( 'ads_container' => true ) );
		$this->assertFalse( wp_script_is( HeadTags::CLICKIO_HANDLE, 'enqueued' ) );
	}

	/**
	 * Registers a stand-in WooSidebars callback on get_header, runs the workaround, and reports where it ended up
	 */
	private function run_woosidebar_workaround() {
		global $sc_site;

		$callback = array( new Woo_Sidebars_Test_Double(), 'replace_sidebars' );
		add_action( 'get_header', $callback );
		$sc_site->fix_woosidebar_hooks();

		$moved = (bool) has_action( 'template_redirect', $callback );
		remove_action( 'get_header', $callback );
		remove_action( 'template_redirect', $callback, 10 );

		return $moved;
	}

	/**
	 * With ad_slots off the WooSidebars workaround still runs; on, it moves nothing
	 */
	function test_ad_slots_flag_switches_woosidebars_off() {
		SC_Settings::get_instance()->reload();
		$this->assertTrue( $this->run_woosidebar_workaround() );

		$_GET[ SC_Settings::OVERRIDE_PARAM ] = 'ad_slots';
		$this->assertFalse( $this->run_woosidebar_workaround() );

		update_option( SC_Settings::SETTINGS_AD_SLOTS, 'on' );
		SC_Settings::get_instance()->reload();
		unset( $_GET[ SC_Settings::OVERRIDE_PARAM ] );
		$this->assertFalse( $this->run_woosidebar_workaround() );

		$_GET[ SC_Settings::OVERRIDE_PARAM ] = '-ad_slots';
		$this->assertTrue( $this->run_woosidebar_workaround() );
		unset( $_GET[ SC_Settings::OVERRIDE_PARAM ] );
	}

	/**
	 * Turning ad_slots on or off clears the page cache; saving it unchanged does not
	 */
	function test_ad_slots_flag_change_clears_page_cache() {
		$GLOBALS['sc_test_page_cache_clears'] = 0;

		update_option( SC_Settings::SETTINGS_AD_SLOTS, 'on' );
		$this->assertSame( 1, $GLOBALS['sc_test_page_cache_clears'] );

		update_option( SC_Settings::SETTINGS_AD_SLOTS, 'on' );
		$this->assertSame( 1, $GLOBALS['sc_test_page_cache_clears'] );

		update_option( SC_Settings::SETTINGS_AD_SLOTS, '' );
		$this->assertSame( 2, $GLOBALS['sc_test_page_cache_clears'] );
	}

	/**
	 * Saving the Publicitat page clears the page cache; saving anything else does not
	 */
	function test_saving_options_page_clears_page_cache() {
		$GLOBALS['sc_test_page_cache_clears'] = 0;

		do_action( 'acf/save_post', 123 );
		do_action( 'acf/save_post', 'options' );
		$this->assertSame( 0, $GLOBALS['sc_test_page_cache_clears'] );

		do_action( 'acf/save_post', ZoneResolver::OPTIONS_ID );
		$this->assertSame( 1, $GLOBALS['sc_test_page_cache_clears'] );
	}
}
