<?php
/**
 * @package Sotcatalà
 */

/**
 * Simple REST client
 */
class SC_RestClient {

	public function get( $url, $use_api_key = false ) {

		$args = array(
			'method' => 'GET',
			'timeout' => 5,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		if ( $use_api_key && defined( 'SC_API_KEY' ) ) {
			$args['headers']['X-SC-Api-Key'] = SC_API_KEY;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$args['headers']['accept-encoding'] = 'identity';
		}

		if (isset($_SERVER['HTTP_USER_AGENT'])){
			$args['user-agent'] = $_SERVER['HTTP_USER_AGENT'];
		}
		
		$response = wp_remote_get( $url, $args );

		$code = wp_remote_retrieve_response_code( $response );
		$result = wp_remote_retrieve_body( $response );
		$error = is_wp_error( $response );

		return array(
			'result' => $result,
			'code' => $code,
			'error' => $error,
		);
	}
}
