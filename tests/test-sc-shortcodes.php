<?php
/**
 * Tests for SC_Shortcodes: the theme's shortcodes render in WordPress's own
 * shortcode pass, so its [[tag]] escape works on every the_content call.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

/**
 * Class SCShortcodesTest
 */
class SCShortcodesTest extends SCTests {

	/**
	 * @var SC_Shortcodes
	 */
	private $handler;

	function set_up() {
		parent::set_up();

		$handler = new SC_Shortcodes();
		$handler->add(
			'sc-prova',
			function ( $atts, $content = '' ) {
				$atts = shortcode_atts( array( 'titol' => '' ), $atts );
				return '<b>RENDERED ' . esc_html( $atts['titol'] ) . '</b>' . $content;
			},
			false
		);
		$handler->setup();
		$this->handler = $handler;
	}

	function tear_down() {
		remove_shortcode( 'sc-prova' );
		parent::tear_down();
	}

	function test_a_theme_shortcode_renders_on_the_content() {
		$this->assertStringContainsString( '<b>RENDERED Hola</b>', apply_filters( 'the_content', '[sc-prova titol="Hola"]' ) );
	}

	function test_an_escaped_theme_shortcode_shows_as_text_every_time() {
		foreach ( array( 1, 2 ) as $pass ) {
			$html = apply_filters( 'the_content', 'Feu servir [[sc-prova]].' );
			$this->assertStringContainsString( 'Feu servir [sc-prova].', $html, "pass $pass" );
			$this->assertStringNotContainsString( 'RENDERED', $html, "pass $pass" );
		}
	}

	function test_excerpts_strip_theme_shortcodes() {
		$this->assertSame( 'Abans  després', strip_shortcodes( 'Abans [sc-prova titol="x"] després' ) );
	}

	function test_enclosed_content_keeps_its_curly_quotes() {
		// What the ia-local pages have always shown; see SC_Shortcodes::add().
		$this->assertStringContainsString( 'l&#8217;ordinador', apply_filters( 'the_content', "[sc-prova]l'ordinador[/sc-prova]" ) );
	}

	function test_the_theme_registers_its_shortcodes() {
		foreach ( array( 'cards', 'card', 'ia-data', 'llista-icones', 'llista-links' ) as $tag ) {
			$this->assertTrue( shortcode_exists( $tag ), $tag );
		}
	}
}
