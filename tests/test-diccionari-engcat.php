<?php
/**
 * Dictionary entries that share their name with one of our tools are kept
 * out of the index, so searches for the tool land on the tool.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class DiccionariEngcatTest
 */
class DiccionariEngcatTest extends SCTests {

	function test_tool_names_are_noindex_in_catalan() {
		$this->assertTrue( SC_Diccionari_engcat::is_noindex( 'corrector', 'cat' ) );
		$this->assertTrue( SC_Diccionari_engcat::is_noindex( 'Traductor', 'cat' ) );
	}

	function test_other_entries_stay_indexed() {
		$this->assertFalse( SC_Diccionari_engcat::is_noindex( 'corrector', 'eng' ) );
		$this->assertFalse( SC_Diccionari_engcat::is_noindex( 'correcció', 'cat' ) );
	}
}
