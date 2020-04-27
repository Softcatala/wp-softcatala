<?php
/**
 * @package Softcatalà
 **/

/**
 * Client for the Conjugador
 */

class SC_Conjugador {

	private $rest_client;

	public static function init() {
		new SC_Conjugador();
	}

	public function __construct( $client = null ) {

		if ( null != $client ) {
			$this->rest_client = $client;
		} else {
			$this->rest_client = new SC_RestClient();
		}
	}

	public function get_verb( $verb) {

		$verb = strtolower( $verb );
		
		$url_api = get_option( 'api_conjugador' );
		$url     = $url_api . 'search/' . $verb;

		$result = $this->rest_client->get( $url );

		if ( $result['error'] ) {
			return $this->return500();
		}

		if ( 200 == $result['code'] ) {
			return $this->build_results( $result['result'], $verb );
		}

		
		return $this->return404( $verb );
	}

	private function build_results( $json_result, $verb) {

		$api_result = json_decode( $json_result , true);

		if ( isset( $api_result[0] ) ) {
			
			$verbinf = array_key_first($api_result[0]);

			$title         = 'Conjugador de verbs: ' . $verbinf . '| Softcatalà';
			$content_title = 'Conjugador de verbs: «' . $verbinf . '»';

			$canonical = '/conjugador-de-verbs/verb/'. $verb .'/';
			
			$verbs = $api_result[0][$verbinf];

			/*
			$temps = array (
					array('singular1', 'jo'),
					array('singular2', 'tu'),
					array('singular3', 'ell, ella, vostè'),
					array('plural1', 'nosaltres'),
					array('plural2', 'vosaltres'),
					array('plural3', 'ells, elles, vostès')
			);
			*/
			$temps = array(	'singular1' => 'jo',
							'singular2' => 'tu',
							'singular3' => 'ell, ella, vostè',
							'plural1' => 'nosaltres',
							'plural2' => 'vosaltres, vós',
							'plural3' => 'ells, elles, vostès'
						);

			$variants = array(	'3' => '(val)',
								'4' => '(bal)',
								'6' => '(val,bal)',
								'7' => '(val,bal)',
								'B' => '(bal)',
								'V' => '(val)',
								'Z' => '(val,bal)'
								);
						
			$model = array(
				'result' => $verbs,
				'temps' => $temps,
				'verbinf' => $verbinf,
				'variants'=> $variants	
			);
			
			$result = Timber::fetch( 'ajax/conjugador-verb.twig', array( 'response' => $model ) );
					
			$description = "";
						
			return new SC_SingleResult( 200, $result, $canonical, $description, $title, $content_title );
		
		}//end if


		return $this->return404( $verb );
		
	}

	private function return404( $verb ) {
		throw_error( '404', 'No Results For This Search' );

		$html = Timber::fetch(
			 'ajax/conjugador-verb-not-found.twig',
			array(
				'verb'     => $verb,
			)
			);

		return new SC_SingleResult( 404, $html, '', '', '', '' );
	}
	private function return500() {
		throw_error( '500', 'Error connecting to API server' );

		return new SC_SingleResult( 500, "S'ha produït un error en contactar amb el servidor. Proveu de nou." );
	}

}
