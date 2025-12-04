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

	
	public function get_paraula( $paraula, $llengua ) {

		$paraula = strtolower( $paraula );

		$url_api = get_option( 'api_diccionari_engcat' );
		$url     = $url_api . 'search/' . $paraula;
				
		$result = $this->rest_client->get( $url );
		
		if ( $result['error'] ) {
			return $this->return500();
		}

		if ( 200 == $result['code'] && isset($result['result'])) {

			$api_result   = json_decode( $result['result'] );
			return $this->build_results( $api_result, $paraula, $llengua );
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

	private function build_results( $result, $paraula, $llengua ) {


		if ( isset( $result->results) && count($result->results) > 0  ) {

			$titol_str = ($llengua == 'cat') ? 'català-anglès' : 'anglès-català';
			
			$title         = $paraula . ' - Diccionari '.$titol_str.' | Softcatalà';
			$content_title = 'Diccionari '.$titol_str.': «' . $paraula . '»';
		
			$result_count = count( $result->results );
	
			$html       = 'Resultats de la cerca per a «<strong>' . $paraula . '</strong>»';
	
			$canonical_lemma = isset($result->canonicalLemma) ? $result->canonicalLemma : $paraula;
			$canonical = home_url() . '/diccionari-angles-catala/'.$llengua.'/paraula/' . $canonical_lemma . '/';
			
			$corpus_direction = ( $llengua === 'eng' ) ? 'eng-cat' : 'cat-eng';
			
			$all_results = array_values( $result->results );
			
			if ( $llengua === 'eng' ) {
				$result_index    = 0;
				$corpus_direction = 'eng-cat';
			} else { // assumim 'cat'
				$result_index    = 1;
				$corpus_direction = 'cat-eng';
			}
			
			$final_results = array();

			if ( isset( $all_results[ $result_index ] ) ) {
					
					$single_entry = $all_results[ $result_index ];

					$single_entry->llengua = $llengua;
					
					if ( count( $single_entry->lemmas ) > 0 ) {
						$single_entry->corpus = $this->get_corpus( $paraula, $corpus_direction );
					}
					
					$final_results = $single_entry;
			}
			
			$result->results = $final_results;
			
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
		$titol_str = ($llengua == 'cat') ? 'català-anglès' : 'anglès-català';

		$title         = 'Lletra ' . $lletra . ' - Diccionari '.$titol_str.' | Softcatalà';
		$content_title = 'Diccionari '.$titol_str.': «' . $lletra . '»';

		$result_count = count( $paraules );
		$result_count_word = ( $result_count > 1 ) ? 'resultats' : 'resultat';

		$html       = 'Paraules i expressions en '.$llengua_str.' que comencen per: «<strong>' . $lletra . '</strong>» (' . $result_count . ' ' . $result_count_word . ') <hr class="clara"/>';

		$canonical = home_url() . '/diccionari-angles-catala/' . $llengua . '/lletra/' . strtoupper($lletra) . '/';

		$html .= Timber::fetch( 'ajax/diccionaris-lletra.twig',
			array(
				'url'=> '/diccionari-angles-catala/' . $llengua . '/paraula',
				'response' => array( 'lletra' => $lletra, 'result' => array('words' => $paraules ) ),
				'cols' => 3,
				'topic' => 'Paraules o expressions',
				'hidetitle' => true
			)
		);

		

		return new SC_SinonimsResult( 200, $html, $lletra, $canonical, $title, $content_title );
	}
	
	private function return404( $paraula, $suggestions = array() ) {
		throw_error( '404', 'No Results For This Search' );

		$html = "No hem trobat cap resultat de cerca";
		
		return new SC_Diccionari_EngCatResult( 404, $html, '', '', '', '' );
			
	}

	private function return500() {
		throw_error( '500', 'Error connecting to API server' );
		return new SC_Diccionari_EngCatResult( 500, "S'ha produït un error en contactar amb el servidor. Proveu una altra vegada." );
	}

	
}
