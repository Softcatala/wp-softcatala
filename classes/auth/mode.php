<?php
/**
 * @package Softcatala
 */

namespace Softcatala\Auth;

/**
 * How much of the login flow Keycloak owns, from the SC_SSO_MODE constant
 * (set in wp-config from the compose environment).
 *
 *   DISABLED  no SSO at all; the callback route is not even registered
 *   ENABLED   Keycloak button alongside the normal WordPress password form
 *   ENFORCED  Keycloak only; password login is refused for everybody
 *
 * Resolved in one place because three modes multiplied across route
 * registration, the login form, the authenticate filter and lost-password
 * is a dozen call sites, and the fallback rules below have to hold at all
 * of them.
 *
 * Two safety rules, both failing towards "the site still lets people in":
 *
 *  - an unrecognised value falls back to DISABLED, so a typo in .env
 *    ('ENFORCE', a stray quote) cannot lock out the whole site;
 *  - ENABLED and ENFORCED both fall back to DISABLED while Config is
 *    incomplete, so half-configured SSO never becomes the only way in.
 *
 * Break-glass is therefore the environment itself: set SC_SSO_MODE=ENABLED,
 * restart, log in with a password. Note that ENFORCED also blocks WordPress
 * recovery mode, which still needs a normal login after its email link.
 */
class Mode {

	const DISABLED = 'DISABLED';
	const ENABLED  = 'ENABLED';
	const ENFORCED = 'ENFORCED';

	/**
	 * @return string One of the three constants above.
	 */
	public static function current() {
		return self::resolve( self::declared(), null !== Config::from_constants() );
	}

	/**
	 * The decision itself, with no environment behind it.
	 *
	 * Separate from current() because the two fallback rules are the whole
	 * safety story and constants cannot be redefined between tests — this is
	 * the seam that lets them be covered.
	 *
	 * @param string $declared   Normalised SC_SSO_MODE value.
	 * @param bool   $configured Whether Config::from_constants() found an issuer.
	 * @return string
	 */
	public static function resolve( $declared, $configured ) {
		if ( ! self::is_known( $declared ) ) {
			return self::DISABLED;
		}

		if ( self::DISABLED === $declared ) {
			return self::DISABLED;
		}

		if ( ! $configured ) {
			return self::DISABLED;
		}

		return $declared;
	}

	/**
	 * The raw configured value, normalised but not validated — what the admin
	 * notice needs in order to say which value was rejected.
	 *
	 * @return string
	 */
	public static function declared() {
		if ( ! defined( 'SC_SSO_MODE' ) ) {
			return self::DISABLED;
		}

		return strtoupper( trim( (string) SC_SSO_MODE ) );
	}

	/**
	 * @param string $mode Candidate value.
	 * @return bool
	 */
	public static function is_known( $mode ) {
		return in_array( $mode, array( self::DISABLED, self::ENABLED, self::ENFORCED ), true );
	}

	/**
	 * Why the declared mode is not the effective one, for the admin notice.
	 *
	 * @param string|null $declared   Defaults to the configured value.
	 * @param bool|null   $configured Defaults to whether Config is complete.
	 * @return string|null 'unknown_value', 'not_configured', or null when the
	 *                     declared mode is in force.
	 */
	public static function downgrade_reason( $declared = null, $configured = null ) {
		$declared   = null === $declared ? self::declared() : $declared;
		$configured = null === $configured ? ( null !== Config::from_constants() ) : $configured;

		if ( self::resolve( $declared, $configured ) === $declared ) {
			return null;
		}

		if ( ! self::is_known( $declared ) ) {
			return 'unknown_value';
		}

		return 'not_configured';
	}

	/**
	 * @return bool Whether the Keycloak routes should exist at all.
	 */
	public static function is_enabled() {
		return self::DISABLED !== self::current();
	}

	/**
	 * @return bool
	 */
	public static function is_enforced() {
		return self::ENFORCED === self::current();
	}
}
