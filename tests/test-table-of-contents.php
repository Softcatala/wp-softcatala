<?php
/**
 * Tests for Softcatala\Content\TableOfContents and the page index it feeds.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Content\TableOfContents;

/**
 * Class TableOfContentsTest
 */
class TableOfContentsTest extends SCTests {

	function test_lists_second_level_headings_with_an_id_in_order() {
		$content = "<!-- wp:heading {\"anchor\":\"blocs\"} -->\n<h2 id=\"blocs\" class=\"wp-block-heading\">Els blocs que hi ha</h2>\n<!-- /wp:heading -->"
			. '<h2>Sense ancoratge</h2><h3 id="sub">Subtítol</h3>'
			. '<h2 class="x" id="taules">Taules <em>i</em> llistes</h2>';

		$this->assertSame(
			array(
				array(
					'id'    => 'blocs',
					'title' => 'Els blocs que hi ha',
				),
				array(
					'id'    => 'taules',
					'title' => 'Taules i llistes',
				),
			),
			TableOfContents::from_content( $content )
		);
	}

	function test_drops_a_trailing_count() {
		$toc = TableOfContents::from_content( '<h2 id="sense-resposta">Sense resposta (20)</h2>' );
		$this->assertSame( 'Sense resposta', $toc[0]['title'] );
	}

	function test_no_anchored_headings_no_index() {
		$this->assertSame( array(), TableOfContents::from_content( '<h2>Un</h2><p>Text</p>' ) );
	}

	function test_the_editor_guide_has_an_index() {
		$toc = TableOfContents::from_content( file_get_contents( get_template_directory() . '/docs/editors/editor-de-blocs.html' ) );
		$this->assertGreaterThan( 5, count( $toc ) );
		$this->assertSame( 'on', $toc[0]['id'] );
	}

	/**
	 * Titles are HTML text cut from the content (entities already encoded),
	 * so the partial prints them as they are.
	 */
	function test_the_index_partial_links_each_heading() {
		$html = Timber::compile(
			'components/index-pagina.twig',
			array(
				'toc' => array(
					array(
						'id'    => 'blocs',
						'title' => 'Els blocs &amp; co',
					),
				),
			)
		);

		$this->assertStringContainsString( 'En aquesta pàgina', $html );
		$this->assertStringContainsString( '<a href="#blocs">Els blocs &amp; co</a>', $html );
	}
}
