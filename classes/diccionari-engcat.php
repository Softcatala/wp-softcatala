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
		$url     = $url_api . '/search/' . $paraula;
		
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

	/**
	 * Get word with automatic language detection
	 * Fetches the word from API and detects the best language based on lemma distribution
	 * Returns minimal response object with only detected_language and canonical_lemma for redirect
	 * 
	 * @param string $paraula The word to search for
	 * @return SC_Diccionari_EngCatResult Response object with detected_language set
	 */
	public function get_paraula_with_language_detection( $paraula ) {
		$paraula = strtolower( $paraula );

		$url_api = get_option( 'api_diccionari_engcat' );
		$url     = $url_api . '/search/' . $paraula;
		
		$result = $this->rest_client->get( $url );
		
		if ( $result['error'] ) {
			return $this->return500();
		}
		
		if ( 200 == $result['code'] && isset($result['result'])) {

			$api_result   = json_decode( $result['result'] );
			return $this->build_results_for_redirect( $api_result, $paraula );
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

			$all_results = array_values( $result->results );

			$valid_indexes = [];
			foreach ($all_results as $i => $entry) {
				if (!empty($entry->lemmas) && count($entry->lemmas) > 0) {
					$valid_indexes[] = $i;
				}
			}
			if (count($valid_indexes) === 1) {

				$result_index = $valid_indexes[0];
				if ($result_index === 0) {
					$llengua = 'eng';
					$corpus_direction = 'eng-cat';
				} else {
					$llengua = 'cat';
					$corpus_direction = 'cat-eng';
				}

			} else {

				if ($llengua === 'eng') {
					$result_index = 0;
					$corpus_direction = 'eng-cat';
				} else {
					$result_index = 1;
					$corpus_direction = 'cat-eng';
				}
			}


			$titol_str = ($llengua == 'cat') ? 'català-anglès' : 'anglès-català';

			$title         = $paraula . ' - Diccionari '.$titol_str.' | Softcatalà';
			$content_title = 'Diccionari '.$titol_str.': «' . $paraula . '»';

			$html       = 'Resultats de la cerca per a «<strong>' . $paraula . '</strong>»';

			$canonical_lemma = isset($result->canonicalLemma) ? $result->canonicalLemma : $paraula;
			$canonical = home_url() . '/diccionari-angles-catala/'.$llengua.'/paraula/' . $canonical_lemma . '/';

			$corpus_direction = ( $llengua === 'eng' ) ? 'eng-cat' : 'cat-eng';

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
		}

	}

	/**
	 * Build minimal response for language detection and redirect
	 * Detects the best language without fetching corpus or rendering templates
	 * 
	 * @param object $result API result object
	 * @param string $paraula The word being searched
	 * @return SC_Diccionari_EngCatResult Minimal response with detected_language
	 */
	private function build_results_for_redirect( $result, $paraula ) {

		if ( isset( $result->results) && count($result->results) > 0  ) {

		$all_results = array_values( $result->results );

		$valid_indexes = [];
		foreach ($all_results as $i => $entry) {
			if (!empty($entry->lemmas) && count($entry->lemmas) > 0) {
				$valid_indexes[] = $i;
			}
		}

		$detected_language = $this->detect_language( $all_results, $valid_indexes, '' );

		// Get canonical lemma
		$canonical_lemma = isset($result->canonicalLemma) ? $result->canonicalLemma : $paraula;

		// Return minimal response with only detected_language and canonical_lemma
		return new SC_Diccionari_EngCatResult( 200, '', $canonical_lemma, '', '', '', $detected_language );
		}

		return $this->return404( $paraula );
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
	
		return new SC_Diccionari_EngCatResult( 200, $html, $lletra, $canonical, $title, $content_title );
	}
	
	private function return404( $paraula, $suggestions = array() ) {
		throw_error( '404', 'No Results For This Search' );

		$html = "No hem trobat cap resultat de cerca.";
		
		return new SC_Diccionari_EngCatResult( 404, $html, '', '', '', '' );
			
	}

	private function return500() {
		throw_error( '500', 'Error connecting to API server' );
		return new SC_Diccionari_EngCatResult( 500, "S'ha produït un error en contactar amb el servidor. Proveu una altra vegada." );
	}

	/**
	 * Detects the best language based on lemmas in results
	 * 
	 * Rules:
	 * - If only 1 valid lemma entry: use that entry's language
	 * - If multiple entries but all same language: use that language
	 * - If multiple languages: use the one with more total lemmas
	 * 
	 * @param array $all_results All result entries from API
	 * @param array $valid_indexes Indexes of entries with valid lemmas
	 * @param string $default_lingua Default language to fall back to
	 * 
	 * @return string The detected language ('eng' or 'cat')
	 */
	private function detect_language( $all_results, $valid_indexes, $default_lingua ) {
		$lemma_counts = array( 'eng' => 0, 'cat' => 0 );

		foreach ( $valid_indexes as $i ) {
			$language = ( $i === 0 ) ? 'eng' : 'cat';
			$lemma_counts[ $language ] += count( $all_results[ $i ]->lemmas );
		}

		// Return the language with more lemmas
		return ( $lemma_counts['eng'] >= $lemma_counts['cat'] ) ? 'eng' : 'cat';
	}

	
}
