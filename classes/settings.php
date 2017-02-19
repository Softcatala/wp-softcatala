<?php
/**
 * @package Softcatala
 */

/**
 * Class SC_Settings
 *
 * Wraps some settings created in the dashboard and exposed
 * in different points in code
 */
class SC_Settings extends SC_Singleton {

	const SETTINGS_LOG_CORRECTOR_USER_EVENTS = 'log_corrector_user_events';
	const SETTINGS_SEND_EMAILS_THESAURUS_ERRORS = 'send_emails_thesaurus_error';

	const SETTINGS = array( self::SETTINGS_LOG_CORRECTOR_USER_EVENTS, self::SETTINGS_SEND_EMAILS_THESAURUS_ERRORS );

	public function get_setting_names() {
		return self::SETTINGS;
	}

	private $setting_values = false;

	public function get_setting_values() {

		if ( ! $this->setting_values ) {

			$ui_settings = $this->get_setting_names();

			$this->setting_values = array();

			foreach ( $ui_settings as $setting ) {
				$this->setting_values[ $setting ] = get_option( $setting, false );
			}
		}

		return $this->setting_values;
	}

	public function get_setting($setting) {

		$values = $this->get_setting_values();

		if ( isset( $values[ $setting ] ) ) {
			return $values[ $setting ];
		}
		else {
			return false;
		}
	}

	public function reload() {
		$this->setting_values = false;
	}
}
