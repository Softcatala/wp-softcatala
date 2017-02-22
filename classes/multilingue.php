<?php
/**
 * @package Softcatalà
 **/

/**
 * Client for the multilingual dictionary
 */
class SC_Multilingue extends SC_Diccionari {

	public static function init() {
		new SC_Multilingue();
	}

	public function __construct() {
		add_shortcode( 'multilingue-stats', array( $this, 'multilingue_stats' ) );
	}

	public function multilingue_stats() {

		$result = wp_cache_get( 'multilingue_stats', 'sc' );

		if ( false === $result ) {

			$stats_loader = new SC_Multilingue_Stats();
			$result = $stats_loader->load();
			wp_cache_set( 'multilingue_stats', $result, 'sc', (3600 * 12) );

		}

		return $result;
	}

	private function return404() {
		throw_error( '404', 'No Results For This Search' );
		return 'Sembla que la paraula que esteu cercant no es troba al diccionari. Heu seleccionat la llengua correcta?';
	}

	private function return500() {
		throw_error( '500', 'Error connecting to API server' );
		return "S'ha produït un error en contactar amb el servidor. Proveu de nou.";
	}

	public function get_paraula($paraula, $lang, $metadata = false ) {

		$url_api = get_option( 'api_diccionari_multilingue' );
		$url = $url_api . 'search/' . $paraula . '?new=1&lang=' . $lang;

		$result = $this->do_api_call( $url );

		if ( $result['error'] ) {
			return $this->return500();
		}

		if ( $result['code'] == 200 ) {
			return $this->buildResults( $result['result'], $paraula, $lang, $metadata );
		}

		return $this->return404();
	}

	public function buildResults( $jsonResult, $paraula, $lang, $metadata ) {

		$apiResult = json_decode( $jsonResult );

		if ( isset( $apiResult[0] ) ) {

			$title = 'Diccionari multilingüe: ' . $paraula . '. Definició i traducció al català, anglès, alemany, francès, italià i espanyol | Softcatalà';
			$content_title = 'Diccionari multilingüe: «' . $paraula . '»';

			$result_count = (count( $apiResult ) > 1) ? 'resultats' : 'resultat';
			$result = 'Resultats de la cerca per: <strong>' . $paraula . '</strong> (' . count( $apiResult ) . ' ' . $result_count . ') <hr class="clara"/>';

			if ( $lang != 'ca' ) {
				$canonical = '/diccionari-multilingue/paraula/' . $apiResult[0]->word_ca . '/';
			}
			else {
				$canonical = '/diccionari-multilingue/paraula/' . $paraula . '/';
			}

			if ( property_exists( $apiResult[0], 'definition_ca' ) ) {
				$description = 'Definició de «' . $paraula .'»: ' .  $apiResult[0]->definition_ca . '. Traduccions al català, anglès, alemany, francès, italià i espanyol';
			}
			else {
				$description = 'Definició de la paraula «' . $paraula .'» i traduccions al català, anglès, alemany, francès, italià i espanyol';
			}

			foreach ( $apiResult as $single_entry ) {

				$source = $this->get_source_link( $single_entry );

				// Unset main source from other sources.
				$refs = (array) $single_entry->references;
				unset( $refs[ $single_entry->source ] );

				$single_entry->references = $refs;

				$model = array(
					'paraula'	=> $paraula,
					'source'	=> $source,
					'result'	=> $single_entry,
				);

				$result .= Timber::fetch( 'ajax/multilingue-paraula.twig', array( 'response' => $model ) );
			}

			if ( $metadata ) {
				return array(
					'content'		=> $result,
					'canonical'		=> $canonical,
					'description'	=> $description,
					'title'			=> $title,
					'content_title'	=> $content_title,
					'result'		=> $result,
				);
			}
			else {
				return $result;
			}
		}//end if

		return $this->return404();

	}

	function get_source_link($result) {
		if ( $result->source == 'wikidata' ) {
			$value = '<a href="https://www.wikidata.org/wiki/' . $result->references->wikidata . '">Wikidata</a>';
		}
		else if ( $result->source == 'wikidictionary_ca' ) {
			$value = '<a href="https://ca.wiktionary.org/wiki/' . $result->references->wikidictionary_ca . '">Viccionari</a>';
		}

		return $value;
	}
}
