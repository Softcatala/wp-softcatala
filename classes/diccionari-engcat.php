<?php
/**
 * @package Softcatalà
 **/

/**
 * Client for the English-Catalan dictionary
 */
class SC_Diccionari_engcat {

	private $rest_client;

	private $path = "/diccionari-angles-catala";

	public static function init() {
		new SC_Diccionari_engcat();
	}

	public function __construct( $client = null ) {
		if ( null != $client ) {
			$this->rest_client = $client;
		} else {
			$this->rest_client = new SC_RestClient();
		}
	}

	
	public function get_paraula( $paraula ) {

		$paraula = strtolower( $paraula );

		$url_api = get_option( 'api_diccionari_engcat' );
		$url     = $url_api . 'search/' . $paraula;
				
		$result = $this->rest_client->get( $url );
		
		if ( $result['error'] ) {
			return $this->return500();
		}

		if ( 200 == $result['code'] && isset($result['result'])) {

			$api_result   = json_decode( $result['result'] );
			return $this->build_results( $api_result, $paraula );
		}

		return $this->return404( $paraula );
	}

	public function get_corpus( $paraula, $direction ) {

		$paraula = strtolower( $paraula );

		$url_api = get_option( 'api_cerca_corpus' );
		
		if($direction == "cat-eng")
			$url     = $url_api . '/search/?target=' . urlencode($paraula);
		else
			$url     = $url_api . '/search/?source=' . urlencode($paraula);
		
		
		$result = $this->rest_client->get( $url );

		if ( 200 == $result['code'] && isset($result['result'])) {

			return $api_result   = json_decode( $result['result'] );
			
		}

		return;
	}

	private function build_results( $result, $paraula ) {


		if ( isset( $result->results) && count($result->results) > 0  ) {

			$title         = $paraula . ' - Diccionari anglès-català | Softcatalà';
			$content_title = 'Diccionari anglès-català: «' . $paraula . '»';
		
			$result_count = count( $result->results );
	
			$html       = 'Resultats de la cerca per a: «<strong>' . $paraula . '</strong>»';
	
			$canonical_lemma = isset($result->canonicalLemma) ? $result->canonicalLemma : $paraula;
			$canonical = '/diccionari-angles-catala'.'/paraula/' . $canonical_lemma . '/';
			
			foreach ( $result->results as $index => $single_entry ) {
				
				if (count($single_entry->lemmas)>0) {
					if ($index === array_key_first($result->results)) {
						$single_entry->corpus = $this->get_corpus($paraula, "eng-cat");
					}else{
						$single_entry->corpus = $this->get_corpus($paraula, "cat-eng");;
					}
				}
			}
			
			$html .= Timber::fetch( 'ajax/diccionari-engcat-resultat.twig', array(
				'results'  => $result->results,
			));
			
			return new SC_Diccionari_EngCatResult( 200, $html, $canonical_lemma, $canonical, $title, $content_title, $result );
		}//end if
		
		
	}

	/* Llistat per llestres */

		
	public function get_lletra( $lletra, $llengua ) {
	
		$lletra = strtolower( $lletra );
		
		$url_api = get_option( 'api_diccionari_engcat' );
		$url     = $url_api . 'index/'.$llengua.'-' . $lletra;
		
		$result = $this->rest_client->get( $url );

		if ( $result['error'] ) {
			return $this->return500();
		}

		if ( 200 == $result['code'] && isset($result['result'])) {
			
			$api_result   = json_decode( $result['result'] );
			return $this->build_index( $lletra, $llengua, $api_result->words );
		}
			
		return $this->notFound( $lletra );
	}

	private function build_index($lletra, $llengua, $paraules) {
		
		$llengua_str = ($llengua == 'cat') ? 'català' : 'anglès';

		$title         = 'Lletra ' . $lletra . ' - Diccionari anglès-català | Softcatalà';
		$content_title = 'Diccionari anglès-català: «' . $lletra . '»';

		
		$result_count = count( $paraules );
		$result_count_word = ( $result_count > 1 ) ? 'resultats' : 'resultat';

		$html       = 'Paraules i expressions en '.$llengua_str.' que comencen per: «<strong>' . $lletra . '</strong>» (' . $result_count . ' ' . $result_count_word . ') <hr class="clara"/>';

		$canonical = '/diccionari-de-sinonims/lletra/' . strtoupper($lletra) . '/';

		$html .= Timber::fetch( 'ajax/diccionaris-lletra.twig',
			array(
				'url'=> '/diccionari-angles-catala/paraula',
				'response' => array( 'lletra' => $lletra, 'result' => array('words' => $paraules ) ),
				'cols' => 3,
				'topic' => 'Paraules o expressions',
				'hidetitle' => true
			)
		);

		

		return new SC_SinonimsResult( 200, $html, $lletra, $canonical, $title, $content_title );
	}
	private function returnNoindexresults( $lletra ){

		$canonical = '/conjugador-de-verbs/lletra/'. $lletra .'/';
		$title = 'Diccionari anglès-català: paraules que comencen per ' . $lletra;
		$content_title =  'Diccionari anglès-català. Paraules que comencen per la lletra «' . $lletra . '»';
		$description = 'Diccionari anglès-català: paraules que comencen per ' . $lletra;
		
		$resposta = 'No hi ha paraules que comecin amb la lletra <strong>'. $lletra . '</strong>.';	
		
		$result = Timber::fetch('ajax/diccionari-engcat-paraula-not-found.twig', array('resposta' => $resposta));

		return new SC_SingleResult( 200, $result, $canonical, $description, $title, $content_title );
	
	}

	private function return404( $paraula, $suggestions = array() ) {
		throw_error( '404', 'No Results For This Search' );

		$html = "No trobat";
		
		return new SC_Diccionari_EngCatResult( 404, $html, '', '', '', '' );
			
	}

	private function return500() {
		throw_error( '500', 'Error connecting to API server' );
		return new SC_Diccionari_EngCatResult( 500, "S'ha produït un error en contactar amb el servidor. Proveu una altra vegada." );
	}

	
}
