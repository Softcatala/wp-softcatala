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
	const SETTINGS_LOG_TRADUCTOR_SOURCE = 'log_traductor_source';
	const SETTINGS_CORRECTOR_SEND_SESSIONID = 'corrector_send_sessionid';
	const SETTINGS_CLICKIO_CMP = 'clickio_cmp';
	const SETTINGS_AD_SLOTS = 'ad_slots';

	const OVERRIDE_PARAM = 'ff';

	private $flags = null;

	/**
	 * Flag definitions from config/feature-flags.php, keyed by option name.
	 */
	public function get_flags() {
		if ( null === $this->flags ) {
			$this->flags = require get_template_directory() . '/config/feature-flags.php';
		}

		return $this->flags;
	}

	public function get_setting_names() {
		return array_keys( $this->get_flags() );
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

	public function get_setting( $setting ) {

		$values = $this->get_setting_values();

		if ( isset( $values[ $setting ] ) ) {
			return $values[ $setting ];
		} else {
			return false;
		}
	}

	/**
	 * Overridable flags forced on or off for this request with ?ff=name,-name.
	 */
	public function get_overrides() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, per-request display toggle.
		$raw = isset( $_GET[ self::OVERRIDE_PARAM ] ) && is_string( $_GET[ self::OVERRIDE_PARAM ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::OVERRIDE_PARAM ] ) ) : '';

		$flags     = $this->get_flags();
		$overrides = array();

		foreach ( explode( ',', $raw ) as $token ) {
			$token = trim( $token );
			$name  = '-' === substr( $token, 0, 1 ) ? substr( $token, 1 ) : $token;

			if ( ! empty( $flags[ $name ]['overridable'] ) ) {
				$overrides[ $name ] = ( $name === $token );
			}
		}

		return $overrides;
	}

	/**
	 * Stored values with this request's overrides applied, for front-end templates.
	 */
	public function get_active_values() {
		return array_merge( $this->get_setting_values(), $this->get_overrides() );
	}

	/**
	 * Whether a flag is on for this request, ?ff= overrides included.
	 *
	 * @param string $setting Flag option name.
	 * @return bool
	 */
	public function is_active( $setting ) {
		return ! empty( $this->get_active_values()[ $setting ] );
	}

	public function reload() {
		$this->setting_values = false;
	}
}
