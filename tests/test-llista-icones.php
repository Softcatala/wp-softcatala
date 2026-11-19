<?php
/**
 * Tests for the icon list: the [llista-icones] shortcode keeps its output
 * (tests/fixtures/llista-icones.json, recorded before its renderer was shared
 * with the sc/llista-icones block), and the block's renderer gives the same.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class LlistaIconesTest
 */
class LlistaIconesTest extends SCTests {

	private function fixture() {
		return json_decode( file_get_contents( __DIR__ . '/fixtures/llista-icones.json' ), true );
	}

	function test_shortcode_output_is_unchanged() {
		$fixture = $this->fixture();

		foreach ( $fixture['out'] as $color => $expected ) {
			$this->assertSame( $expected, do_shortcode( "[llista-icones$color]\n{$fixture['body']}\n[/llista-icones]" ), "color:$color" );
		}
	}

	function test_shared_renderer_matches_the_shortcode() {
		$items = array(
			array(
				'icon'  => 'language',
				'title' => '',
				'text'  => 'La satisfacció de fer una feina efectiva.',
			),
			array(
				'icon'  => 'sort-alpha-asc',
				'title' => 'Traductors i correctors',
				'text'  => 'Només cal un bon català.',
			),
		);

		$this->assertSame(
			do_shortcode( "[llista-icones color=\"blancgris\"]\nlanguage||La satisfacció de fer una feina efectiva.\nsort-alpha-asc|Traductors i correctors|Només cal un bon català.\n[/llista-icones]" ),
			SC_Shortcodes_IconList::render( $items, 'blancgris' )
		);
	}

	function test_seven_names_work_too() {
		$html = SC_Shortcodes_IconList::render(
			array(
				array(
					'icon'  => 'users',
					'title' => 'Comunitat',
					'text'  => 'Text.',
				),
			)
		);

		$this->assertStringContainsString( '<i class="fas fa-users"></i>', $html );
	}

	function test_no_items_no_markup() {
		$this->assertSame( '', SC_Shortcodes_IconList::render( array() ) );
	}
}
