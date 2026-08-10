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
		// WordPress core lives under /wp/, so clients must not hardcode
		// /wp-admin/... — they read the refresh URL from this tag instead.
		$refresh_url = admin_url( 'admin-ajax.php' ) . '?action=sc_get_token';
		echo '<meta name="sc-token-refresh-url" content="' . esc_url( $refresh_url ) . '">' . "\n";

		$token = sc_get_api_token();
		if ( $token ) {
			echo '<meta name="sc-token" content="' . esc_attr( $token ) . '">' . "\n";
		}
	}
);

/**
 * Same-origin refresh endpoint so long-lived pages can replace the token
 * from the meta tag once it nears expiry. Served from the transient, so
 * the auth server is still contacted at most once per hour.
 */
function sc_ajax_get_token() {
	$token = sc_get_api_token();

	if ( ! $token ) {
		wp_send_json( array( 'token' => '' ), 503 );
	}

	wp_send_json( array( 'token' => $token ) );
}
add_action( 'wp_ajax_sc_get_token', 'sc_ajax_get_token' );
add_action( 'wp_ajax_nopriv_sc_get_token', 'sc_ajax_get_token' );
