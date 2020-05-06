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

	public function get_verb( $verb , $infinitiu = "", $ajaxquery = false) {

		$verb = strtolower( $verb );
		$infinitiu = strtolower( $infinitiu );
		
		$url_api = get_option( 'api_conjugador' );
		
		if($infinitiu){
			$url     = $url_api . 'search/' . $infinitiu;
		}else{
			$url     = $url_api . 'search/' . $verb;
		}

		$result = $this->rest_client->get( $url );

		if ( $result['error'] ) {
			return $this->return500();
		}

		if ( 200 == $result['code'] ) {
			
			return $this->build_results( $result['result'], $verb, $infinitiu, $ajaxquery );
		}
		
		
		return $this->notFound( $verb );
	}

	public function get_lletra( $lletra ) {

		
		if (strlen( $lletra ) != '1' ) {
			$resposta = 'Esteu utilitzant la cerca per lletra. Heu cercat <strong>'. $lletra . '</strong>. La cerca només pot contenir una lletra';
			return $this->notFound( $resposta );
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
			
		return $this->notFound( $lletra );
	}


	private function build_results( $json_result, $verb, $infinitiu = "", $ajaxquery = false) {

		
		if(!is_string($json_result)){
			return $this->notFound($verb);
		}
				
		$api_result = json_decode( $json_result , true);

		if( !is_array($api_result)){
			return $this->notFound($verb);	
		}

		if(count($api_result) == 0){
			return $this->notFound($verb);
		}
		
		if(!$ajaxquery){
			
			$true_infinitive = $this->searchInfinitive($verb, $api_result);

			if ($true_infinitive){
				return $this->returnInfinitive( $true_infinitive, $verb, $verb  );
			}else{
				return $this->return404();
			}
			
		}

		if(count($api_result) == 1){
			return $this->returnInfinitive( $api_result[0], $verb, $infinitiu );
		}
			
		if(count($api_result) > 1){

			if($infinitiu){
				return $this->returnInfinitive( $this->searchInfinitive($infinitiu, $api_result), $verb, $infinitiu );
			}else{	
				return $this->returnInfinitives( $api_result, $verb );	
			}
			
		}

			return $this->notFound( $verb );
		
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

	private function notFound( $verb ) {
		
		throw_error( '404', 'No Results For This Search' );

		$canonical = '/conjugador-de-verbs/';
		$title = 'Conjugador de verbs | Softcatalà';
		$content_title =  'Conjugador de verbs.  «' . $verb . '»';
		$description = '';

		$html = Timber::fetch(
			 'ajax/conjugador-verb-not-found.twig',
			array(
				'resposta'     =>  'No hem trobat la forma verbal «'.$verb.'» en el conjugador',
			)
			);
		return new SC_SingleResult( 404, $html, $canonical, $description, $title, $content_title );
		
	}
	
	
	private function return404() {
		
		return false;
	}

	private function return500() {
		
		throw_error( '500', 'Error connecting to API server' );
		return new SC_SingleResult( 500, "S'ha produït un error en contactar amb el servidor. Proveu de nou." );
	}


	private function returnInfinitive($api_result, $verb, $infinitiu = ""){
		
			
			if(!$infinitiu){
				$infinitiu = array_key_first($api_result);
			}
			
			$verbs = $api_result[$infinitiu];

			$cinf = array_search('Infinitiu', array_column($verbs, 'tense'));
			$infinitive_title =$verbs[$cinf]['singular1']['0']['word'];
			
			$title         = 'Conjugació del verb ' . $infinitive_title . '| Softcatalà';
			$content_title = 'Conjugació del verb «' . $verb . '»';

			$canonical = '/conjugador-de-verbs/verb/'. $infinitiu .'/';
			
			$temps = array(	'singular1' => 'jo',
							'singular2' => 'tu',
							'singular3' => 'ell, ella, vostè',
							'plural1' => 'nosaltres',
							'plural2' => 'vosaltres, vós',
							'plural3' => 'ells, elles, vostès'
						);

			$variants = array(	                '3' => '(val.)',
								'4' => '(bal.)',
								'6' => '(val., bal.)',
								'7' => '(val., bal.)',
								'C' => '(cent.)',					  
								'B' => '(bal.)',
								'V' => '(val.)',
								'Z' => '(val., bal.)'
								);

			$model = array(
				'result' => $verbs,
				'temps' => $temps,
				'verbinf' => $infinitiu,
				'variants'=> $variants,
				'infinitive_title' => $infinitive_title	
			);
			
			$result = Timber::fetch( 'ajax/conjugador-verb.twig', array( 'response' => $model ) );
					
			$description = "";
						
			return new SC_SingleResult( 200, $result, $canonical, $description, $title, $content_title );



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

	private function returnInfinitives( $api_result , $verb ){
		
		$canonical = '/conjugador-de-verbs/';
		$title = 'Conjugació del verb ' . $verb;
		$content_title =  'Conjugació del verb «' . $verb . '»';
		$description = 'Conjugació del verb «' . $verb . '»';
		
		$model = array(
			'verbs' =>  $api_result,
			'verb' => $verb
		);
		$result = Timber::fetch( 'ajax/conjugador-infinitius.twig', array( 'response' => $model ) );
		
		return new SC_SingleResult( 200, $result, $canonical, $description, $title, $content_title );
	
	}

	private function searchInfinitive ($verb, $array){
		foreach ($array as $key => $val) {
			if (array_key_exists ( $verb , $val )) {
					return $array[$key];
			}
		}
		return false;
	}

}
