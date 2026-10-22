<?php
/**
 * Class SC_SettingsTest
 *
 * @package Softcatala
 */

require_once('sc_tests.php');

/**
 * Tests of the settings class
 */
class SC_SettingsTest extends SCTests {

	/**
	 * Testing singleton behavior
     */
	function test_cannot_intantiate_new_instance() {
		
		$reflection = new \ReflectionClass('SC_Settings');
		$constructor = $reflection->getConstructor();
		$this->assertFalse($constructor->isPublic());
		
	}

	/**
	 * Non defined options are false
	 */
	function test_settings_are_false() {
		$values = SC_Settings::get_instance()->get_setting_values();

		foreach($values as $setting_value) {
			$this->assertFalse($setting_value);
		}
	}

	/**
	 * Defined options have their value
	 */
	function test_custom_setting_is_stored() {

		$settings = SC_Settings::get_instance();
		$value = $settings->get_setting(SC_Settings::SETTINGS_LOG_CORRECTOR_USER_EVENTS);

		$this->assertFalse($value);

		update_option(SC_Settings::SETTINGS_LOG_CORRECTOR_USER_EVENTS, true);

		$settings->reload();
		$value = $settings->get_setting(SC_Settings::SETTINGS_LOG_CORRECTOR_USER_EVENTS);
		$this->assertTrue($value);
	}

	/**
	 * Every configured flag carries the labels the dashboard renders
	 */
	function test_flags_have_labels() {
		$flags = SC_Settings::get_instance()->get_flags();

		$this->assertContains( SC_Settings::SETTINGS_CLICKIO_CMP, SC_Settings::get_instance()->get_setting_names() );
		foreach ( $flags as $name => $flag ) {
			$this->assertNotEmpty( $flag['title'], $name );
			$this->assertNotEmpty( $flag['description'], $name );
		}
	}

	/**
	 * The dashboard renders one checkbox per flag, checked when enabled
	 */
	function test_dashboard_renders_flag_switches() {
		update_option( SC_Settings::SETTINGS_CLICKIO_CMP, 'on' );
		$settings = SC_Settings::get_instance();
		$settings->reload();

		$html = \Timber\Timber::compile( 'admin/sc-dash.twig', array( 'sections' => array(), 'settings' => $settings ) );

		foreach ( $settings->get_setting_names() as $name ) {
			$this->assertStringContainsString( 'name="' . $name . '"', $html );
		}
		$this->assertStringContainsString( 'name="clickio_cmp" checked="checked"', $html );
		$this->assertStringNotContainsString( 'name="corrector_send_sessionid" checked', $html );
	}

	/**
	 * The Clickio consent scripts are only emitted when the flag is on
	 */
	function test_footer_clickio_follows_flag() {
		$off = \Timber\Timber::compile( 'footer.twig', array( 'feature_flags' => array( 'clickio_cmp' => false ) ) );
		$on  = \Timber\Timber::compile( 'footer.twig', array( 'feature_flags' => array( 'clickio_cmp' => 'on' ) ) );

		$this->assertStringNotContainsString( 'clickiocmp.com', $off );
		$this->assertStringNotContainsString( '__tcfapiLocator', $off );
		$this->assertStringContainsString( 'clickiocmp.com', $on );
		$this->assertStringContainsString( '__tcfapiLocator', $on );
	}

	function tear_down() {
		unset( $_GET[ SC_Settings::OVERRIDE_PARAM ] );
		parent::tear_down();
	}

	/**
	 * ?ff=name turns an overridable flag on for the request without touching the stored option
	 */
	function test_querystring_turns_flag_on() {
		$settings = SC_Settings::get_instance();
		$settings->reload();
		$_GET['ff'] = 'clickio_cmp';

		$this->assertSame( array( 'clickio_cmp' => true ), $settings->get_overrides() );
		$this->assertTrue( $settings->get_active_values()['clickio_cmp'] );
		$this->assertFalse( $settings->get_setting( SC_Settings::SETTINGS_CLICKIO_CMP ) );
	}

	/**
	 * ?ff=-name turns an enabled overridable flag off
	 */
	function test_querystring_turns_flag_off() {
		update_option( SC_Settings::SETTINGS_CLICKIO_CMP, 'on' );
		$settings = SC_Settings::get_instance();
		$settings->reload();
		$_GET['ff'] = '-clickio_cmp';

		$this->assertFalse( $settings->get_active_values()['clickio_cmp'] );
		$this->assertSame( 'on', $settings->get_setting( SC_Settings::SETTINGS_CLICKIO_CMP ) );
	}

	/**
	 * Unknown names, non-overridable flags and malformed values are ignored
	 */
	function test_querystring_ignores_unknown_and_non_overridable_flags() {
		$settings = SC_Settings::get_instance();

		$_GET['ff'] = 'log_traductor_source, nope,,--clickio_cmp';
		$this->assertSame( array(), $settings->get_overrides() );

		$_GET['ff'] = array( 'clickio_cmp' );
		$this->assertSame( array(), $settings->get_overrides() );
	}

	/**
	 * Overrides reach front-end templates but never the dashboard checkboxes
	 */
	function test_overrides_do_not_leak_into_dashboard() {
		$settings = SC_Settings::get_instance();
		$settings->reload();
		$_GET['ff'] = 'clickio_cmp';

		$html = \Timber\Timber::compile( 'admin/sc-dash.twig', array( 'sections' => array(), 'settings' => $settings ) );

		$this->assertStringNotContainsString( 'name="clickio_cmp" checked', $html );
		$this->assertStringContainsString( '?ff=clickio_cmp', $html );
		$this->assertStringNotContainsString( '?ff=log_traductor_source', $html );
	}
}
