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
}
