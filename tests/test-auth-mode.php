<?php
/**
 * Tests for Softcatala\Auth\Mode — the SC_SSO_MODE fallback rules.
 *
 * Both rules exist so that a misconfiguration cannot lock everybody out of
 * the site, which under ENFORCED has no password fallback to recover with.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Auth\Mode;

/**
 * Class AuthModeTest
 */
class AuthModeTest extends SCTests {

	public function test_declared_modes_pass_through_when_configured() {
		$this->assertEquals( Mode::DISABLED, Mode::resolve( Mode::DISABLED, true ) );
		$this->assertEquals( Mode::ENABLED, Mode::resolve( Mode::ENABLED, true ) );
		$this->assertEquals( Mode::ENFORCED, Mode::resolve( Mode::ENFORCED, true ) );
	}

	/**
	 * A typo in .env must not be read as "lock the site".
	 */
	public function test_unknown_value_falls_back_to_disabled() {
		foreach ( array( 'ENFORCE', 'enforced ', '"ENFORCED"', '', 'true', '1' ) as $value ) {
			$this->assertEquals(
				Mode::DISABLED,
				Mode::resolve( $value, true ),
				sprintf( 'value %s should fall back to DISABLED', var_export( $value, true ) )
			);
		}
	}

	/**
	 * Half-filled credentials must never become the only way in.
	 */
	public function test_enforced_without_config_falls_back_to_disabled() {
		$this->assertEquals( Mode::DISABLED, Mode::resolve( Mode::ENFORCED, false ) );
		$this->assertEquals( Mode::DISABLED, Mode::resolve( Mode::ENABLED, false ) );
	}

	public function test_declared_is_normalised() {
		$this->assertTrue( Mode::is_known( 'ENFORCED' ) );
		$this->assertFalse( Mode::is_known( 'enforced' ) );
		$this->assertFalse( Mode::is_known( 'ENFORCE' ) );
	}

	public function test_downgrade_reason_distinguishes_the_two_causes() {
		$this->assertNull( Mode::downgrade_reason( Mode::ENFORCED, true ) );
		$this->assertEquals( 'unknown_value', Mode::downgrade_reason( 'ENFORCE', true ) );
		$this->assertEquals( 'not_configured', Mode::downgrade_reason( Mode::ENFORCED, false ) );
	}

	/**
	 * The theme ships with no SC_SSO_MODE defined, so the default install is
	 * untouched by any of this.
	 */
	public function test_default_install_is_disabled() {
		$this->assertEquals( Mode::DISABLED, Mode::current() );
		$this->assertFalse( Mode::is_enabled() );
		$this->assertFalse( Mode::is_enforced() );
	}
}
