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

	public function get_lletra( $lletra ) {

		
		if (strlen( $lletra ) != '1' ) {
			$resposta = 'Esteu utilitzant la cerca per lletra. Heu cercat <strong>'. $lletra . '</strong>. La cerca només pot contenir una lletra';
			return $this->return404( $resposta );
		}

		$lletra = strtolower( $lletra );
		$url_api = get_option( 'api_conjugador' );
		$url     = $url_api . 'index/' . $lletra;
			
		$result = $this->rest_client->get( $url );

		if ( $result['error'] ) {
			return $this->return500();
		}

		if ( 200 == $result['code'] ) {
			return $this->build_index( $result['result'], $lletra );
		}
			
		return $this->return404( $lletra );
	}


	private function build_results( $json_result, $verb) {

		if(!is_string($json_result)){
			return $this->return404('No hem trobat el verb '.$verb.' al conjugador');
		}
				
		$api_result = json_decode( $json_result , true);

		if( !is_array($api_result)){
			return $this->return404('No hem trobat el verb '.$verb.' al conjugador');	
		}

		if(count($api_result) == 0){
			return $this->return404('No hem trobat el verb '.$verb.' al conjugador');
		}

		
		if(count($api_result) == 1){
			
			$key = array_key_first($api_result[0]);
			$verbs = $api_result[0][$key];
			
			$title         = 'Conjugador de verbs: ' . $verb . '| Softcatalà';
			$content_title = 'Conjugador de verbs: «' . $verb . '»';

			$canonical = '/conjugador-de-verbs/verb/'. $key .'/';
			
						
			// Cerquem l'infinitiu real pel títol
			$key = array_search('Infinitiu', array_column($verbs, 'form'));
			$verbinf =$verbs[$key]['singular1']['0']['word'];

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


		}
				
		if(count($api_result) > 1){

			return $this->returnInfinitives( $verb, $api_result );
		
		}//end if

		$resposta = " El verb «".$verb."» que heu cercat, no es troba al conjugador.";
		
		return $this->return404( $resposta );
		
	}
	private function build_index ( $json_result, $lletra) {

		
		if(!is_string($json_result)){
			return $this->returnNoindexresults($lletra);
		}

		$api_result = json_decode( $json_result , true);
				
		if( !is_array($api_result)){
			return $this->returnNoindexresults($lletra);	
		}
		
		if(count($api_result) == 0){
			return $this->returnNoindexresults($lletra);
		}
		
		$model = array(
			'lletra' => $lletra,
			'verbs' =>  $api_result
		);

		$canonical = '/conjugador-de-verbs/lletra/'. $lletra .'/';
		
						
		$model = array(
			'lletra' => $lletra,
			'verbs' =>  $api_result
		);
		
		$canonical = '/conjugador-de-verbs/lletra/'. $lletra .'/';
		$title = 'Conjugador de verbs: verbs que comencen per ' . $lletra;
		$content_title =  'Conjugador de verbs. Verbs que comencen per la lletra «' . $lletra . '»';
		$description = "'Conjugador de verbs: verbs que comencen per ' . $lletra;";
		
		$result = Timber::fetch( 'ajax/conjugador-lletra.twig', array( 'response' => $model ) );

		return new SC_SingleResult( 200, $result, $canonical, $description, $title, $content_title );

	}

	private function return404( $resposta ) {
		
		throw_error( '404', 'No Results For This Search' );

		$html = Timber::fetch(
			 'ajax/conjugador-verb-not-found.twig',
			array(
				'resposta'     => $resposta,
			)
			);

		return new SC_SingleResult( 404, $html, '', '', '', '' );
	}

	private function return500() {
		
		throw_error( '500', 'Error connecting to API server' );
		return new SC_SingleResult( 500, "S'ha produït un error en contactar amb el servidor. Proveu de nou." );
	}

	private function returnNoindexresults( $lletra ){

		$canonical = '/conjugador-de-verbs/lletra/'. $lletra .'/';
		$title = 'Conjugador de verbs: verbs que comencen per ' . $lletra;
		$content_title =  'Conjugador de verbs. Verbs que comencen per la lletra «' . $lletra . '»';
		$description = 'Conjugador de verbs: verbs que comencen per ' . $lletra;
		
		$resposta = 'No hi ha verbs que comecin amb la lletra <strong>'. $lletra . '</strong>.';	
		
		
		$result = Timber::fetch('ajax/conjugador-verb-not-found.twig', array('resposta' => $resposta));

		return new SC_SingleResult( 200, $result, $canonical, $description, $title, $content_title );
	
	}

	private function returnInfinitives( $verb, $api_result ){

		$canonical = '/conjugador-de-verbs/cerca/'. $verb .'/';
		$title = 'Conjugador de verbs:  ' . $verb;
		$content_title =  'Conjugador de verbs.  «' . $verb . '»';
		$description = 'Conjugador de verbs.  «' . $verb . '»';
		
		
			$resposta .="<ul>";
			foreach($api_result as $verbs){
				
				$infititiu = array_key_first($verbs);

				$resposta .="<li>";
				$resposta .= $verb . ' amb l\'infitiu <a href="/conjugador-de-verbs/verb/'.$infititiu.'">'.$infititiu.'</a>';
				$resposta .="</li>";
			}
			$resposta .="</ul>";	
		
		
		$result = Timber::fetch('ajax/conjugador-verb-not-found.twig', array('resposta' => $resposta));

		return new SC_SingleResult( 200, $result, $canonical, $description, $title, $content_title );
	
	}

	private function get_infitive($api_result){


	}

}
