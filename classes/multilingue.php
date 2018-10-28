<?php
/**
 * @package Softcatalà
 **/

/**
 * Client for the multilingual dictionary
 */
class SC_Multilingue {

	private $rest_client;

	public static function init() {
		new SC_Multilingue();
	}

	public function __construct( $client = null ) {
		add_shortcode( 'multilingue-stats', array( $this, 'multilingue_stats' ) );

		if ( null != $client ) {
			$this->rest_client = $client;
		} else {
			$this->rest_client = new SC_RestClient();
		}
	}

	public function multilingue_stats() {

		$result = wp_cache_get( 'multilingue_stats', 'sc' );

		if ( false === $result ) {

			$stats_loader = new SC_Multilingue_Stats();
			$result       = $stats_loader->load();
			wp_cache_set( 'multilingue_stats', $result, 'sc', ( 3600 * 12 ) );

		}

		return $result;
	}

	public function get_paraula( $paraula, $lang ) {

		$paraula = strtolower( $paraula );
		$lang    = strtolower( $lang );

		$url_api = get_option( 'api_diccionari_multilingue' );
		$url     = $url_api . 'search/' . $paraula . '?lang=' . $lang;

		$result = $this->rest_client->get( $url );

		if ( $result['error'] ) {
			return $this->return500();
		}

		if ( 200 == $result['code'] ) {
			return $this->build_results( $result['result'], $paraula, $lang );
		}

		$suggestions = array();
		if ( isset( $result['result'] ) ) {
			$suggestions = $this->get_suggestions( $lang, $result['result'] );
		}

		return $this->return404( $paraula, $lang, $suggestions );
	}

	private function get_suggestions( $lang, $json ) {

		$suggestions = array();
		$api_result   = json_decode( $json );

		if ( isset( $api_result[0] ) ) {
			foreach ( $api_result as $single_entry ) {

				$field = 'word_' . $lang;

				$suggestions[] = strtolower( $single_entry->$field );
			}

			sort( $suggestions );

			$suggestions = array_unique( $suggestions );
		}

		return $suggestions;
	}

	private function build_results( $json_result, $paraula, $lang ) {

		$api_result = json_decode( $json_result );

		if ( isset( $api_result[0] ) ) {

			$title         = 'Diccionari multilingüe: ' . $paraula . '. Definició i traducció al català, anglès, alemany, francès, italià i espanyol | Softcatalà';
			$content_title = 'Diccionari multilingüe: «' . $paraula . '»';

			$result_count = ( count( $api_result ) > 1 ) ? 'resultats' : 'resultat';
			$result       = 'Resultats de la cerca per: <strong>' . $paraula . '</strong> (' . count( $api_result ) . ' ' . $result_count . ') <hr class="clara"/>';

			if ( 'ca' != $lang ) {
				$canonical = '/diccionari-multilingue/paraula/' . $api_result[0]->word_ca . '/';
			} else {
				$canonical = '/diccionari-multilingue/paraula/' . $paraula . '/';
			}

			if ( property_exists( $api_result[0], 'definition_ca' ) ) {
				$description = 'Definició de «' . $paraula . '»: ' . $api_result[0]->definition_ca . '. Traduccions al català, anglès, alemany, francès, italià i espanyol';
			} else {
				$description = 'Definició de la paraula «' . $paraula . '» i traduccions al català, anglès, alemany, francès, italià i espanyol';
			}

			foreach ( $api_result as $single_entry ) {

				$source = $this->get_source_link( $single_entry );

				// Unset main source from other sources.
				$refs = (array) $single_entry->references;
				unset( $refs[ $single_entry->source ] );

				$single_entry->references = $refs;

				$model = array(
					'paraula' => $paraula,
					'source'  => $source,
					'result'  => $single_entry,
				);

				$result .= Timber::fetch( 'ajax/multilingue-paraula.twig', array( 'response' => $model ) );
			}

			return new SC_MultilingueResult( 200, $result, $canonical, $description, $title, $content_title, $result );
		}//end if

		return $this->return404( $paraula, $lang );
	}

	private function return404( $paraula, $lang, $suggestions = array() ) {
		throw_error( '404', 'No Results For This Search' );

		$langname = $this->get_langname( $lang );

		$html = Timber::fetch(
			 'ajax/multilingue-paraula-not-found.twig',
			array(
				'paraula'     => $paraula,
				'lang'        => $lang,
				'langname'    => $langname,
				'suggestions' => $suggestions,
			)
			);

		return new SC_MultilingueResult( 404, $html, '', '', '', '', $suggestions );
	}

	private function get_langname( $lang ) {
		switch ( $lang ) {
			case 'ca':
				return 'català';

			case 'es':
				return 'espanyol';

			case 'en':
				return 'anglès';

			case 'fr':
				return 'francès';

			case 'de':
				return 'alemany';

			case 'it':
				return 'italià';

			default:
				return $lang;
		}
	}

	private function return500() {
		throw_error( '500', 'Error connecting to API server' );

		return new SC_MultilingueResult( 500, "S'ha produït un error en contactar amb el servidor. Proveu de nou." );
	}

	private function get_source_link( $result ) {
		if ( 'wikidata' == $result->source ) {
			$value = '<a href="https://www.wikidata.org/wiki/' . $result->references->wikidata . '">Wikidata</a>';
		} else if ( 'wikidictionary_ca' == $result->source ) {
			$value = '<a href="https://ca.wiktionary.org/wiki/' . $result->references->wikidictionary_ca . '">Viccionari</a>';
		}

		return $value;
	}
}
