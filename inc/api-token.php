<?php

function sc_get_api_token() {
	$token = get_transient( 'sc_api_token' );
	if ( $token !== false ) {
		return $token;
	}

	if ( ! defined( 'SC_TOKEN_ENDPOINT' ) || ! defined( 'SC_TOKEN_ISSUE_SECRET' ) ) {
		return false;
	}

	$response = wp_remote_post(
		SC_TOKEN_ENDPOINT,
		array(
			'headers' => array( 'X-Issue-Secret' => SC_TOKEN_ISSUE_SECRET ),
			'timeout' => 3,
		)
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return false;
	}

	$body  = json_decode( wp_remote_retrieve_body( $response ), true );
	$token = $body['token'] ?? '';

	if ( $token ) {
		set_transient( 'sc_api_token', $token, HOUR_IN_SECONDS );
	}

	return $token ?: false;
}

add_action(
	'wp_head',
	function () {
		$token = sc_get_api_token();
		if ( $token ) {
			echo '<meta name="sc-token" content="' . esc_attr( $token ) . '">' . "\n";
		}
	}
);
