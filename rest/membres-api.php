<?php

/**
 * REST API endpoint listing Softcatalà's active members as JSON.
 *
 * GET sc/v1/membres — the people the /membres archive shows (author.php):
 * every user whose status_member is 1 or was never set. An explicit 0 is a
 * former member and is left out, so the list itself is the active set; the
 * caller treats anyone missing from it as no longer active.
 *
 * Logged-in only, like sc/v1/projectes. The address is exposed solely to
 * callers allowed to read user emails.
 */

function sc_register_membres_api() {
	register_rest_route(
		'sc/v1',
		'/membres',
		array(
			'methods'             => 'GET',
			'callback'            => 'sc_rest_membres',
			'permission_callback' => 'is_user_logged_in',
		)
	);
}
add_action( 'rest_api_init', 'sc_register_membres_api' );

/**
 * The active-member query: the same meta clause author.php uses.
 *
 * @return WP_User[]
 */
function sc_active_members() {
	return get_users(
		array(
			'orderby'    => 'login',
			'order'      => 'ASC',
			'meta_query' => array(
				'relation' => 'OR',
				get_meta_query_value( 'status_member', 1, '=', '' ),
				get_meta_query_value( 'status_member', '', 'NOT EXISTS', '' ),
			),
		)
	);
}

function sc_rest_membres() {
	$with_email = current_user_can( 'list_users' );
	$membres    = array();

	foreach ( sc_active_members() as $user ) {
		$membre = array(
			// user_nicename, as sc/v1/projectes names responsables.
			'username'     => $user->user_nicename,
			'display_name' => $user->display_name,
			'telegram'     => get_user_meta( $user->ID, 'telegram', true ) ?: null,
		);
		if ( $with_email ) {
			$membre['email'] = $user->user_email;
		}
		$membres[] = $membre;
	}

	return rest_ensure_response( $membres );
}
