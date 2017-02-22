<?php
/**
 * @package Sotcatalà
 */

/**
 * Abstract class with shared code across SC's dictionaries
 */
class SC_Diccionari {

	public function do_api_call( $url ) {

		$response = wp_remote_get(
			$url,
			array(
				'method' => 'GET',
				'timeout' => 5,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			)
		);

		$code = wp_remote_retrieve_response_code( $response );
		$result = wp_remote_retrieve_body( $response );
		$error = is_wp_error( $response );

		return array( 'result' => $result, 'code' => $code, 'error' => $error );
	}
}
