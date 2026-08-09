<?php
/**
 * Class IaDataShortcodeTest
 *
 * @package Softcatala
 */

require_once('sc_tests.php');

/**
 * Tests of the [ia-data] shortcode's rendering
 */
class IaDataShortcodeTest extends SCTests {

	const URL = 'https://example.org/data.json';

	/**
	 * Serves $document as the JSON body of every HTTP request, so the
	 * shortcode's wp_safe_remote_get() never leaves the process.
	 */
	private function render( $atts, $document ) {
		add_filter(
			'pre_http_request',
			function () use ( $document ) {
				return array(
					'headers'  => array( 'content-type' => 'application/json' ),
					'body'     => wp_json_encode( $document ),
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			}
		);

		$atts = array_merge(
			array(
				'url'   => self::URL,
				'cache' => '0',
			),
			$atts
		);

		return ( new SC_Shortcodes_IaData() )->shortcode( $atts );
	}

	private function document( $overrides = array() ) {
		return array_merge(
			array(
				'text' => array(
					'model'    => 'Model',
					'dim'      => 'Dimensions',
					'clam_pct' => 'CLAM%',
				),
				'data' => array(
					array(
						'model'    => 'model-a',
						'dim'      => 768,
						'clam_pct' => 68.12,
					),
					array(
						'model'    => 'model-b',
						'dim'      => 1024,
						'clam_pct' => 47.5,
					),
				),
			),
			$overrides
		);
	}

	/**
	 * With no "decimals" anywhere, the graph shows as many decimals as the
	 * data itself, uniformly across rows (68.12 and 47.5 -> 2 decimals)
	 */
	function test_graph_infers_decimals_from_the_data() {
		$html = $this->render( array( 'format' => 'graph' ), $this->document() );

		$this->assertStringContainsString( '>68.12</div>', $html );
		$this->assertStringContainsString( '>47.50</div>', $html );
	}

	/**
	 * Inference caps itself at 4 decimals
	 */
	function test_inferred_decimals_cap_at_four() {
		$document = $this->document();

		$document['data'][0]['clam_pct'] = 0.123456789;
		$document['data'][1]['clam_pct'] = 0.1;

		$html = $this->render( array( 'format' => 'graph' ), $document );

		$this->assertStringContainsString( '>0.1235</div>', $html );
		$this->assertStringContainsString( '>0.1000</div>', $html );
	}

	/**
	 * An explicit "decimals" in the metric's configuration wins over
	 * inference; the shortcode attribute wins over inference too
	 */
	function test_explicit_decimals_win_over_inference() {
		$document = $this->document(
			array( 'metrics' => array( 'clam_pct' => array( 'decimals' => 1 ) ) )
		);

		$html = $this->render( array( 'format' => 'graph' ), $document );
		$this->assertStringContainsString( '>68.1</div>', $html );

		$html = $this->render( array( 'format' => 'graph', 'decimals' => '3' ), $this->document() );
		$this->assertStringContainsString( '>68.120</div>', $html );
	}

	/**
	 * The generated threshold label uses the cutoff's own precision, not
	 * the rows': an integer cutoff renders "≥ 50", not "≥ 50.00"
	 */
	function test_threshold_label_uses_the_cutoff_precision() {
		$document = $this->document(
			array( 'metrics' => array( 'clam_pct' => array( 'success' => array( 'min' => 50, 'color' => '#388e3c' ) ) ) )
		);

		$html = $this->render( array( 'format' => 'graph' ), $document );

		$this->assertStringContainsString( '≥ 50</span>', $html );
	}

	/**
	 * "subtitle", "caption" and "threshold_label" can come from the
	 * metric's JSON configuration
	 */
	function test_graph_text_comes_from_the_json_configuration() {
		$document = $this->document(
			array(
				'metrics' => array(
					'clam_pct' => array(
						'subtitle'        => 'usable a partir del 50 per cent',
						'caption'         => 'una nota al peu',
						'threshold_label' => 'llindar usable',
						'success'         => array(
							'min'   => 50,
							'color' => '#388e3c',
						),
					),
				),
			)
		);

		$html = $this->render( array( 'format' => 'graph' ), $document );

		$this->assertStringContainsString( 'chart-subtitle">usable a partir del 50 per cent<', $html );
		$this->assertStringContainsString( 'una nota al peu', $html );
		$this->assertStringContainsString( 'llindar usable</span>', $html );
	}

	/**
	 * "label" is an accepted alias of "threshold_label", but loses to it
	 */
	function test_label_is_an_alias_of_threshold_label() {
		$document = $this->document(
			array(
				'metrics' => array(
					'clam_pct' => array(
						'label'   => 'llindar via label',
						'success' => array(
							'min'   => 50,
							'color' => '#388e3c',
						),
					),
				),
			)
		);

		$html = $this->render( array( 'format' => 'graph' ), $document );
		$this->assertStringContainsString( 'llindar via label</span>', $html );

		$document['metrics']['clam_pct']['threshold_label'] = 'llindar canonic';

		$html = $this->render( array( 'format' => 'graph' ), $document );
		$this->assertStringContainsString( 'llindar canonic</span>', $html );
		$this->assertStringNotContainsString( 'llindar via label', $html );
	}

	/**
	 * The shortcode attributes still win over the JSON's text
	 */
	function test_shortcode_attributes_win_over_the_json_text() {
		$document = $this->document(
			array( 'metrics' => array( 'clam_pct' => array( 'subtitle' => 'subtitle del json' ) ) )
		);

		$html = $this->render(
			array(
				'format'   => 'graph',
				'subtitle' => 'subtitle del shortcode',
			),
			$document
		);

		$this->assertStringContainsString( 'subtitle del shortcode', $html );
		$this->assertStringNotContainsString( 'subtitle del json', $html );
	}

	/**
	 * The table also infers decimals per column: integer columns stay
	 * integers, float columns keep the data's precision
	 */
	function test_table_infers_decimals_per_column() {
		$html = $this->render( array( 'format' => 'table' ), $this->document() );

		$this->assertStringContainsString( '<td>768</td>', $html );
		$this->assertStringContainsString( '<td>68.12</td>', $html );
		$this->assertStringContainsString( '<td>47.50</td>', $html );
	}
}
