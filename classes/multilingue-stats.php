<?php
/**
 * @package Softcatala
 */

/**
 * Gets and parses Multlingual dictionary stats
 */
class SC_Multilingue_Stats {

	public function __construct() {

	}

	public function load() {

		$url_api = get_option( 'api_diccionari_multilingue' );

		$api_call = do_json_api_call( $url_api . '/statistics' );
		$statistics = json_decode( $api_call );

		$stats = '';

		if ( $statistics ) {
			$data = array();

			$data['ca_labels'] = $this->count( $statistics, 'ca_labels' );
			$data['ca_descs'] = $this->count( $statistics, 'ca_descs' );
			$data['en_labels'] = $this->count( $statistics, 'en_labels' );
			$data['en_descs'] = $this->count( $statistics, 'en_descs' );
			$data['fr_labels'] = $this->count( $statistics, 'fr_labels' );
			$data['fr_descs'] = $this->count( $statistics, 'fr_descs' );
			$data['de_labels'] = $this->count( $statistics, 'de_labels' );
			$data['de_descs'] = $this->count( $statistics, 'de_descs' );
			$data['es_labels'] = $this->count( $statistics, 'es_labels' );
			$data['es_descs'] = $this->count( $statistics, 'es_descs' );
			$data['it_labels'] = $this->count( $statistics, 'it_labels' );
			$data['it_descs'] = $this->count( $statistics, 'it_descs' );

			$data['date'] = $statistics->wikidata->date;
			$data['images'] = $statistics->wikidata->images;

			$stats = Timber::fetch( 'ajax/multilingue-stats.twig', array( 'statistics' => $data ) );
		}

		return $stats;
	}

	function count($statistics, $key) {

		$wikidata = (array) $statistics->wikidata;
		$wikidictionary = (array) $statistics->wikidictionary;
		$value = $wikidata[ $key ];

		if ( isset( $wikidictionary[ $key ] ) && $wikidictionary[ $key ] ) {
				$value += $wikidictionary[ $key ];
		}

		return $value;
	}
}
