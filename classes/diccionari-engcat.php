<?php
/**
 * @package Softcatalà
 **/

/**
 * Client for the English-Catalan dictionary
 */
class SC_Diccionari_engcat {

	private $rest_client;

	private $path = "/diccionari-eng-cat";

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
		$url     = $url_api . '/search/' . $paraula;
		
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

				
			$title         = 'Diccionari : ' . $paraula . '. Diccionari Anglès-Català en línia | Softcatalà';
			$content_title = 'Diccionari Anglès-Català: «' . $paraula . '»';
		
			$result_count = count( $result->results );
	
			$html       = 'Resultats de la cerca per a: «<strong>' . $paraula . '</strong>»';
	
			$canonical_lemma = isset($result->canonicalLemma) ? $result->canonicalLemma : $paraula;
			$canonical = '/diccionari-eng-cat'.'/paraula/' . $canonical_lemma . '/';
			
			foreach ( $result->results as $index => $single_entry ) {
				
				if (count($single_entry->groupsLemmas)>0) {

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

	private function return404( $paraula, $suggestions = array() ) {
		throw_error( '404', 'No Results For This Search' );

		$html = "No trobat";
		
		return new SC_Diccionari_EngCatResult( 404, $html, '', '', '', '' );
			
	}

	private function return500() {
		throw_error( '500', 'Error connecting to API server' );
		return new SC_Diccionari_EngCatResult( 500, "S'ha produït un error en contactar amb el servidor. Proveu de nou." );
	}

	private function prepareWordOriginal() {

		return;

	}
	
}
