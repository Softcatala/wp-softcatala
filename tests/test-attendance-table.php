<?php
/**
 * Tests for the assistencia block style: cell counting and the totals written
 * into the footer of core Table blocks.
 *
 * @package Softcatala
 */

require_once( 'sc_tests.php' );

use Softcatala\Blocks\AttendanceTable;

/**
 * Class AttendanceTableTest
 */
class AttendanceTableTest extends SCTests {

	function set_up() {
		parent::set_up();

		if ( false === has_filter( 'render_block_core/table', array( AttendanceTable::class, 'filter' ) ) ) {
			AttendanceTable::register();
		}
	}

	/**
	 * Builds a table block with the assistencia style.
	 *
	 * @param string $body  Rows for tbody.
	 * @param string $tfoot Optional tfoot element.
	 * @return string
	 */
	private function table( $body, $tfoot = '' ) {
		return '<figure class="wp-block-table is-style-assistencia"><table><thead><tr>'
			. '<th>Persona</th>'
			. '<th class="has-text-align-center" data-align="center">Matí</th>'
			. '<th class="has-text-align-center" data-align="center">Sopar</th>'
			. '</tr></thead><tbody>' . $body . '</tbody>' . $tfoot . '</table></figure>';
	}

	/**
	 * Three-row body: 5 people at Matí (2 children), 2 people at Sopar (1 child).
	 *
	 * @return string
	 */
	private function trobada_body() {
		return '<tr><td>Anna</td><td class="has-text-align-center" data-align="center">✓</td><td class="has-text-align-center" data-align="center">✓ +1 nena</td></tr>'
			. '<tr><td>Pere</td><td class="has-text-align-center" data-align="center">Sí</td><td class="has-text-align-center" data-align="center">sis</td></tr>'
			. '<tr><td>Marta</td><td class="has-text-align-center" data-align="center">✓ +2 nens</td><td class="has-text-align-center" data-align="center"></td></tr>';
	}

	/**
	 * Reads the tfoot rows back as class + cell texts.
	 *
	 * @param string $html Table HTML.
	 * @return array
	 */
	private function footer( $html ) {
		$doc = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$rows = array();
		foreach ( $doc->getElementsByTagName( 'tfoot' ) as $tfoot ) {
			foreach ( $tfoot->getElementsByTagName( 'tr' ) as $tr ) {
				$cells = array();
				foreach ( $tr->childNodes as $cell ) {
					if ( $cell instanceof DOMElement ) {
						$cells[] = trim( $cell->textContent );
					}
				}
				$rows[] = array( 'class' => $tr->getAttribute( 'class' ), 'cells' => $cells );
			}
		}
		return $rows;
	}

	/**
	 * @return array
	 */
	public function provider_count_cell() {
		return array(
			'mark'                 => array( '✓', 1, 0 ),
			'mark and companion'   => array( '✓ +1', 2, 0 ),
			'mark, companion, nen' => array( 'Sí +1 nen', 2, 1 ),
			'mark, two nens'       => array( '✓ +2 nens', 3, 2 ),
			'bare companion'       => array( '+1', 1, 0 ),
			'large companion'      => array( '+10', 10, 0 ),
			'nbsp before companion' => array( '✓&nbsp;+1', 2, 0 ),
			'wrapped mark'         => array( '<strong>✓</strong>', 1, 0 ),
			'empty'                => array( '', 0, 0 ),
			'sis'                  => array( 'sis', 0, 0 ),
			'sí, potser'           => array( 'sí, potser', 0, 0 ),
			'question mark'        => array( '?', 0, 0 ),
			'potser'               => array( 'potser', 0, 0 ),
			'uppercase X'          => array( 'X', 1, 0 ),
			'uppercase SI'         => array( 'SI', 1, 0 ),
			'capitalised Sí'       => array( 'Sí', 1, 0 ),
			'emoji check mark'     => array( '✅', 1, 0 ),
			'heavy check + FE0F'   => array( "✔\u{FE0F}", 1, 0 ),
			'nena is a child'      => array( '✓ +1 nena', 2, 1 ),
			'INFANTS is a child'   => array( '✓ +1 INFANTS', 2, 1 ),
			'nenes is a child'     => array( '✓ +2 nenes', 3, 2 ),
			'xiquet is a child'    => array( '✓ +1 xiquet', 2, 1 ),
			'xiqueta is a child'   => array( 'Sí +1 xiqueta', 2, 1 ),
			'xiquets is a child'   => array( '✓ +2 Xiquets', 3, 2 ),
			'xiquetes is a child'  => array( '+2 xiquetes', 2, 2 ),
			'unmarked companion'   => array( 'sis +2', 0, 0 ),
			'non-breaking space'   => array( "✓\u{00A0}+1", 2, 0 ),
		);
	}

	/**
	 * @dataProvider provider_count_cell
	 */
	function test_count_cell( $text, $people, $children ) {
		$this->assertSame(
			array( 'people' => $people, 'children' => $children ),
			AttendanceTable::count_cell( $text )
		);
	}

	function test_filter_ignores_other_styles() {
		$html = $this->table( $this->trobada_body() );
		foreach ( array( 'foo', 'is-style-stripes', 'is-style-assistencia-x' ) as $class ) {
			$this->assertSame( $html, AttendanceTable::filter( $html, array( 'attrs' => array( 'className' => $class ) ) ), $class );
		}
		$this->assertSame( $html, AttendanceTable::filter( $html, array( 'attrs' => array() ) ) );
	}

	function test_filter_acts_on_style_token() {
		$html = $this->table( $this->trobada_body() );
		$out  = AttendanceTable::filter( $html, array( 'attrs' => array( 'className' => 'foo is-style-assistencia' ) ) );
		$this->assertNotSame( $html, $out );
		$this->assertStringContainsString( 'sc-assistencia-total', $out );
	}

	function test_stale_total_is_replaced() {
		$html = $this->table(
			$this->trobada_body(),
			'<tfoot><tr><td>Total</td><td class="has-text-align-center" data-align="center">14</td><td class="has-text-align-center" data-align="center"></td></tr></tfoot>'
		);

		$rows = $this->footer( AttendanceTable::apply_totals( $html ) );

		$this->assertSame( 'sc-assistencia-total', $rows[0]['class'] );
		$this->assertSame( array( 'Total', '5', '2' ), $rows[0]['cells'] );
		$this->assertSame( 'sc-assistencia-infants', $rows[1]['class'] );
		$this->assertSame( array( 'dels quals, infants', '2', '1' ), $rows[1]['cells'] );
		$this->assertCount( 2, $rows );
	}

	function test_missing_tfoot_is_created() {
		$out  = AttendanceTable::apply_totals( $this->table( $this->trobada_body() ) );
		$rows = $this->footer( $out );

		$this->assertSame( 'sc-assistencia-total', $rows[0]['class'] );
		$this->assertSame( array( 'Total', '5', '2' ), $rows[0]['cells'] );
		$this->assertStringContainsString( '<td class="has-text-align-center" data-align="center">5</td>', $out );
	}

	function test_total_label_is_kept_when_present() {
		$html = $this->table(
			$this->trobada_body(),
			'<tfoot><tr><td>Suma</td><td></td><td></td></tr></tfoot>'
		);
		$rows = $this->footer( AttendanceTable::apply_totals( $html ) );
		$this->assertSame( 'Suma', $rows[0]['cells'][0] );
	}

	function test_children_row_removed_when_no_children() {
		$body = '<tr><td>Anna</td><td>✓</td><td>✓ +1</td></tr>';
		$html = $this->table(
			$body,
			'<tfoot><tr><td>Total</td><td>0</td><td>0</td></tr>'
			. '<tr class="sc-assistencia-infants"><td>dels quals, infants</td><td>1</td><td>1</td></tr></tfoot>'
		);

		$rows = $this->footer( AttendanceTable::apply_totals( $html ) );

		$this->assertCount( 1, $rows );
		$this->assertSame( array( 'Total', '1', '2' ), $rows[0]['cells'] );
	}

	function test_unrelated_footer_row_is_preserved() {
		$html = $this->table(
			$this->trobada_body(),
			'<tfoot><tr><td>Total</td><td>14</td><td></td></tr>'
			. '<tr><td>Nota</td><td>x</td><td>y</td></tr></tfoot>'
		);

		$rows = $this->footer( AttendanceTable::apply_totals( $html ) );

		$this->assertCount( 3, $rows );
		$this->assertSame( array( 'Total', '5', '2' ), $rows[0]['cells'] );
		$this->assertSame( 'sc-assistencia-infants', $rows[1]['class'] );
		$this->assertSame( '', $rows[2]['class'] );
		$this->assertSame( array( 'Nota', 'x', 'y' ), $rows[2]['cells'] );
	}

	function test_short_row_counts_only_what_it_has() {
		$body = '<tr><td>Jordi</td></tr><tr><td>Anna</td><td>✓</td></tr>';
		$rows = $this->footer( AttendanceTable::apply_totals( $this->table( $body ) ) );

		$this->assertSame( array( 'Total', '1', '0' ), $rows[0]['cells'] );
	}

	function test_mark_survives_round_trip_as_character() {
		$out = AttendanceTable::apply_totals( $this->table( $this->trobada_body() ) );

		$this->assertStringContainsString( '✓', $out );
		$this->assertStringNotContainsString( '&#10003;', $out );
		$this->assertStringNotContainsString( 'â', $out );
	}

	function test_figure_and_figcaption_survive() {
		$html = str_replace(
			'</table></figure>',
			'</table><figcaption>Nota de la trobada</figcaption></figure>',
			$this->table( $this->trobada_body() )
		);

		$out = AttendanceTable::apply_totals( $html );

		$this->assertStringContainsString( '<figure class="wp-block-table is-style-assistencia">', $out );
		$this->assertStringContainsString( '<figcaption>Nota de la trobada</figcaption>', $out );
		$this->assertStringContainsString( '</figure>', $out );
	}

	function test_input_without_table_is_unchanged() {
		$html = '<p>Res a veure</p>';
		$this->assertSame( $html, AttendanceTable::apply_totals( $html ) );
	}

	function test_table_without_tbody_is_unchanged() {
		$html = '<figure class="wp-block-table"><table><tr><td>✓</td></tr></table></figure>';
		$this->assertSame( $html, AttendanceTable::apply_totals( $html ) );
	}

	function test_do_blocks_applies_totals() {
		$markup = '<!-- wp:table {"className":"is-style-assistencia"} -->'
			. '<figure class="wp-block-table is-style-assistencia"><table><thead><tr><th>Persona</th><th>Matí</th><th>Sopar</th></tr></thead>'
			. '<tbody><tr><td>Anna</td><td>✓</td><td>✓ +1 nena</td></tr>'
			. '<tr><td>Pere</td><td>Sí</td><td>sis</td></tr>'
			. '<tr><td>Marta</td><td>✓ +2 nens</td><td></td></tr></tbody>'
			. '<tfoot><tr><td>Total</td><td>14</td><td></td></tr></tfoot></table></figure>'
			. '<!-- /wp:table -->';

		$out  = do_blocks( $markup );
		$rows = $this->footer( $out );

		$this->assertSame( array( 'Total', '5', '2' ), $rows[0]['cells'] );
		$this->assertSame( array( 'dels quals, infants', '2', '1' ), $rows[1]['cells'] );
	}
}
