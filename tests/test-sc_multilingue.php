<?php

require_once('sc_tests.php');

class SC_MultilingueTest  extends SCTests {

	private $rest_client;

	function test_get_paraula_when_500_returns_500() {

		$this->rest_client = $this->getMock('SC_RestClient');

		$remote = array( 'result' => '', 'code' => 500, 'error' => true );;

		$this->rest_client->expects($this->once())
		                  ->method('get')
		                  ->will($this->returnValue( $remote ));

		$sc_multilingue = new SC_Multilingue( $this->rest_client );

		$result = $sc_multilingue->get_paraula( 'foo' , false);

		$this->assertEquals( 500, $result->status );
		$this->assertEquals ( 'S\'ha produït un error en contactar amb el servidor. Proveu una altra vegada.', $result->html );
	}

	function test_get_paraula_when_404_returns_404() {

		global $wp_query;

		$this->rest_client = $this->getMock('SC_RestClient');

		$remote = array( 'result' => '', 'code' => 404, 'error' => false );;

		$this->rest_client->expects($this->once())
			->method('get')
			->will($this->returnValue( $remote ));

		$sc_multilingue = new SC_Multilingue( $this->rest_client );

		$result = $sc_multilingue->get_paraula( 'foo' , false);

		$this->assertEquals( 404, $result->status );
		$this->assertContains ( '«foo», la paraula que heu cercat, no es troba al diccionari.', $result->html );

		$this->assertTrue( $wp_query->is_404 );
	}

	function test_get_paraula_when_empty_list_returns_404() {

		global $wp_query;

		$this->rest_client = $this->getMock('SC_RestClient');

		$remote = array( 'result' => '[]', 'code' => 200, 'error' => false );;

		$this->rest_client->expects($this->once())
		                  ->method('get')
		                  ->will($this->returnValue( $remote ));

		$sc_multilingue = new SC_Multilingue( $this->rest_client );

		$result = $sc_multilingue->get_paraula( 'foo' , false);

		$this->assertEquals( 404, $result->status );
		$this->assertContains ( '«foo», la paraula que heu cercat, no es troba al diccionari.', $result->html);

		$this->assertTrue( $wp_query->is_404 );
	}

	function test_get_paraula_when_exists_return_correct() {

		global $wp_query;

		$this->rest_client = $this->getMock('SC_RestClient');

		$remote = array( 'result' => $this->json, 'code' => 200, 'error' => false );;

		$this->rest_client->expects($this->once())
		                  ->method('get')
		                  ->will($this->returnValue( $remote ));

		$sc_multilingue = new SC_Multilingue( $this->rest_client );

		$result = $sc_multilingue->get_paraula( 'foo' , false);

		$this->assertEquals( 200, $result->status );
		$this->assertContains ( '<h1>foo</h1>', $result->html );

		$this->assertFalse( $wp_query->is_404 );
	}

	private $json = <<<'EOF'
[
    {
        "definition_it": "mobile",
        "word_en": "table",
        "definition_es": "mueble cuyo cometido es proporcionar una superficie horizontal elevada del suelo",
        "word_fr": "table",
        "quality": 3,
        "definition_ca": "moble, amb diferents utilitats dom\u00e8stiques, que est\u00e0 format per unes potes i un tauler pla que proporciona una superf\u00edcie horitzontal elevada del terra",
        "word_de": "Tisch",
        "image": "Tisch.png",
        "definition_fr": "type de meuble",
        "word_it": "tavolo",
        "word_es": "mesa",
        "definition_de": "M\u00f6belst\u00fcck",
        "references": {
            "gec": "0147926",
            "wikidata": "Q14748",
            "wikiquote_ca": "Taula"
        },
        "source": "wikidata",
        "word_ca": "taula",
        "definition_en": "piece of furniture with a flat top"
    },
    {
        "word_en": "Taula",
        "word_fr": "Taula",
        "quality": 1,
        "definition_ca": "tipus de construcci\u00f3 de la cultura talai\u00f2tica t\u00edpica i exclusiva de l'illa de Menorca",
        "word_de": "Taula",
        "word_es": "Taula",
        "references": {
            "gec": "0215369",
            "wikidata": "Q940097"
        },
        "source": "wikidata",
        "word_ca": "taula"
    },
    {
        "word_en": "table",
        "word_fr": "Table",
        "quality": 0,
        "source": "wikidata",
        "word_de": "Datenbanktabelle",
        "definition_fr": "ensemble de donn\u00e9es organis\u00e9es sous forme d'un tableau",
        "word_it": "Tabella (database)",
        "word_es": "Tabla",
        "references": {
            "wikidata": "Q278425"
        },
        "word_ca": "taula",
        "definition_en": "set of data elements in databases"
    },
    {
        "word_en": "panel",
        "definition_es": "tabl\u00f3n usado como soporto de pinturas",
        "word_fr": "panneau de bois",
        "quality": 0,
        "definition_ca": "pe\u00e7a de fusta com a suport pict\u00f2ric",
        "word_de": "Paneel",
        "definition_fr": "support de la peinture",
        "word_it": "panel",
        "word_es": "panel",
        "definition_de": "d\u00fcnnes Holzbrett zur Bemalung",
        "references": {
            "wikidata": "Q1348059"
        },
        "source": "wikidata",
        "word_ca": "taula",
        "definition_en": "thin wooden plank used to paint on"
    }
]
EOF;
}