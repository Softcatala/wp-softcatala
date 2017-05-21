<?php
/**
 * @package Sotcatalà
 */

/**
 * Simple REST client
 */
class SC_RestClient {

	public function get( $url ) {

		$args = array(
			'method' => 'GET',
			'timeout' => 5,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$args['headers']['accept-encoding'] = 'identity';
		}

		$response = wp_remote_get( $url, $args );

		$code = wp_remote_retrieve_response_code( $response );
		$result = wp_remote_retrieve_body( $response );
		$error = is_wp_error( $response );

		return array( 'result' => $result, 'code' => $code, 'error' => $error );
	}
}
