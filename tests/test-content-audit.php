<?php
/**
 * Tests for Softcatala\Blocks\ContentAudit, the analysis behind
 * `wp sc blocks audit`.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Blocks\ContentAudit;

/**
 * Class ContentAuditTest
 */
class ContentAuditTest extends SCTests {

	private function audit( $content ) {
		$audit = new ContentAudit( array( 'caption', 'gallery', 'ia-data', 'cards', 'card' ), array( 'core/paragraph', 'core/table', 'core/freeform', 'core/html' ) );
		return $audit->audit( $content );
	}

	function test_formats() {
		$this->assertSame( 'empty', $this->audit( "  \n" )['format'] );
		$this->assertSame( 'classic', $this->audit( '<p>Hola</p>' )['format'] );
		$this->assertSame( 'block', $this->audit( "<!-- wp:paragraph -->\n<p>Hola</p>\n<!-- /wp:paragraph -->" )['format'] );
	}

	function test_counts_registered_shortcodes_only() {
		$result = $this->audit( '[caption id="1"]<img src="x">Foto[/caption] [ia-data url="x"] [ia-data url="y"] d\'altres [cops], en canvi [es]' );

		$this->assertSame(
			array(
				'ia-data' => 2,
				'caption' => 1,
			),
			$result['shortcodes']
		);
	}

	function test_nested_shortcodes_count_the_outer_one() {
		$result = $this->audit( '[cards][card title="A"]x[/card][card title="B"]y[/card][/cards]' );
		$this->assertSame( array( 'cards' => 1 ), $result['shortcodes'] );
	}

	function test_escaped_shortcode_is_not_a_use() {
		$this->assertSame( array(), $this->audit( 'Escriviu [[gallery]] per a una galeria.' )['shortcodes'] );
	}

	function test_footnotes() {
		$this->assertSame( 2, $this->audit( 'Un text[1. Primera nota] i un altre[2. Segona]. [cops]' )['footnotes'] );
	}

	function test_legacy_classes_and_debris() {
		$content = '<table class="prettytable1"></table><table class="prettytable"></table>'
			. '<p class="petit">a</p><p class="fons-gris">b</p><div class="req-banner"></div><div class="req-flex"></div>'
			. '<span class="mw-headline">h</span><div class="thumb tright"></div><p class="MsoNormal">w</p>'
			. '<p class="has-background">no</p>';

		$this->assertSame(
			array(
				'prettytable' => 2,
				'req-banner'  => 2,
				'mediawiki'   => 2,
				'petit'       => 1,
				'fons-gris'   => 1,
				'pasted'      => 1,
			),
			$this->audit( $content )['classes']
		);
	}

	function test_one_element_counts_once_per_bucket() {
		// "thumb tright" is one element with two MediaWiki classes.
		$this->assertSame( array( 'mediawiki' => 1 ), $this->audit( '<div class="thumb tright"></div>' )['classes'] );
	}

	function test_inline_styles() {
		$this->assertSame( 2, $this->audit( '<p style="color:red">a</p><span style=\'font-weight:bold\'>b</span><p data-style="x">c</p>' )['inline_styles'] );
	}

	function test_block_content() {
		$content = "<!-- wp:paragraph -->\n<p>a</p>\n<!-- /wp:paragraph -->\n\n"
			. "<!-- wp:cover -->\n<div class=\"wp-block-cover\"><!-- wp:paragraph -->\n<p>b</p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:cover -->\n\n"
			. "<!-- wp:html -->\n<div>c</div>\n<!-- /wp:html -->\n\n"
			. "<!-- wp:freeform --><p>d</p><!-- /wp:freeform -->\n\n"
			. '<p>Fora de cap bloc</p>';

		$result = $this->audit( $content );

		$this->assertSame( array( 'core/cover' => 1 ), $result['blocks_not_allowed'] );
		$this->assertSame( 1, $result['html_blocks'] );
		$this->assertSame( 2, $result['classic_blocks'] );
	}

	function test_classic_content_has_findings_even_when_clean() {
		$this->assertTrue( ContentAudit::has_findings( $this->audit( '<p>Hola</p>' ) ) );
		$this->assertFalse( ContentAudit::has_findings( $this->audit( "<!-- wp:paragraph -->\n<p>Hola</p>\n<!-- /wp:paragraph -->" ) ) );
		$this->assertFalse( ContentAudit::has_findings( $this->audit( '' ) ) );
	}

	function test_summary() {
		$summary = array();
		$summary = ContentAudit::add_to_summary( $summary, 'page', $this->audit( '[ia-data url="x"][ia-data url="y"]<p class="petit" style="x">a</p>' ) );
		$summary = ContentAudit::add_to_summary( $summary, 'page', $this->audit( "<!-- wp:paragraph -->\n<p>a</p>\n<!-- /wp:paragraph -->" ) );
		$summary = ContentAudit::add_to_summary( $summary, 'post', $this->audit( '[ia-data url="z"] Nota[1. x]' ) );

		$this->assertSame(
			array(
				'posts'   => 2,
				'block'   => 1,
				'classic' => 1,
				'empty'   => 0,
			),
			$summary['formats']['page']
		);
		$this->assertSame(
			array(
				'posts' => 2,
				'uses'  => 3,
			),
			$summary['shortcodes']['ia-data']
		);
		$this->assertSame( 1, $summary['classes']['petit']['posts'] );
		$this->assertSame( 1, $summary['other']['footnotes']['posts'] );
		$this->assertSame( 1, $summary['other']['inline_styles']['uses'] );
	}

	function test_for_site_includes_theme_shortcodes() {
		$result = ContentAudit::for_site()->audit( '[llista-icones] [caption]x[/caption] [tabs][tab title="a"]b[/tab][/tabs]' );

		$this->assertArrayHasKey( 'llista-icones', $result['shortcodes'] );
		$this->assertArrayHasKey( 'caption', $result['shortcodes'] );
		$this->assertArrayHasKey( 'tabs', $result['shortcodes'] );
	}
}
