<?php
/**
 * @package Softcatalà
 **/

/**
 * Client for the sinonyms dictionary
 */
class SC_Sinonims {

	private $rest_client;

	public static function init() {
		new SC_Sinonims();
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

		$url_api = get_option( 'api_diccionari_sinonims' );
		$url     = $url_api . 'search/' . $paraula;

		$result = $this->rest_client->get( $url );

		if ( $result['error'] ) {
			return $this->return500();
		}

		$suggestions = array();
		if ( 200 == $result['code'] && isset($result['result'])) {

			$api_result   = json_decode( $result['result'] );
			if ( isset($api_result->results) && count($api_result->results) > 0) {
				return $this->build_results( $api_result, $paraula );
			}

			if  ( isset($api_result->alternatives) && count($api_result->alternatives) > 0) {
				$suggestions = $this->get_suggestions( $api_result );
			}
		}

		return $this->return404( $paraula, $suggestions );
	}

	private function get_suggestions( $result ) {

		$suggestions = array();

		if ( isset( $result['alternatives'] ) ) {
			return $result['alternatives'];
		}

		return $suggestions;
	}

	private function build_results( $result, $paraula ) {

		if ( isset( $result->results) && count($result->results) > 0  ) {

			$title         = 'Diccionari de sinònims: ' . $paraula . '. Diccionari de sinònims de català en línia | Softcatalà';
			$content_title = 'Diccionari de sinònims: «' . $paraula . '»';

			$result_count = ( count( $result->results ) > 1 ) ? 'resultats' : 'resultat';
			$html       = 'Resultats de la cerca per a: «<strong>' . $paraula . '</strong>» (' . count( $result->results ) . ' ' . $result_count . ') <hr class="clara"/>';

			$canonical_lemma = isset($result->canonicalLemma) ? $result->canonicalLemma : $paraula;
			$canonical = '/diccionari-de-sinonims/paraula/' . $canonical_lemma . '/';

			if ( isset($result->alternatives) && count($result->alternatives) > 1 ) {
				$html .= Timber::fetch( 'ajax/sinonims-alternatives.twig', array( 'alternatives' => $result->alternatives ) );
			}

			foreach ( $result->results as $single_entry ) {

				$html .= Timber::fetch( 'ajax/sinonims-paraula.twig', array(
					'paraula' => $paraula,
					'result'  => $single_entry,
				));
			}

			return new SC_SinonimsResult( 200, $html, $canonical_lemma, $canonical, $title, $content_title, $result );
		}//end if

		return $this->return404( $paraula );
	}

	private function return404( $paraula, $suggestions = array() ) {
		throw_error( '404', 'No Results For This Search' );

		$html = Timber::fetch(
			 'ajax/sinonims-paraula-not-found.twig',
				array(
					'paraula'     => $paraula,
					'suggestions' => $suggestions,
				)
			);

		return new SC_SinonimsResult( 404, $html, '', '', '', '', $suggestions );
	}

	private function return500() {
		throw_error( '500', 'Error connecting to API server' );

		return new SC_MultilingueResult( 500, "S'ha produït un error en contactar amb el servidor. Proveu de nou." );
	}
}
