<?php

/**
 * REST API endpoint for updating a tasca's estat_tasca term.
 *
 * PATCH sc/v1/tasca/{id}/estat
 */

/**
 * Register the tasques REST API endpoint.
 */
function sc_register_tasques_api() {
	register_rest_route(
		'sc/v1',
		'/tasca/(?P<id>\d+)/estat',
		array(
			'methods'             => 'PATCH',
			'callback'            => 'sc_rest_update_tasca_estat',
			'permission_callback' => 'sc_rest_tasca_permissions',
			'args'                => array(
				'id'    => array(
					'description'       => 'ID de la tasca a actualitzar.',
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'estat' => array(
					'description'       => 'Slug de l\'estat (terme de la taxonomia estat_tasca).',
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}

/**
 * Permission callback for the tasca PATCH endpoint.
 *
 * @param WP_REST_Request $request Full REST request.
 * @return true|WP_Error True if allowed; WP_Error otherwise.
 */
function sc_rest_tasca_permissions( $request ) {
	// 1. Must be logged in.
	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_not_logged_in',
			__( 'Heu d\'iniciar sessió per modificar tasques.', 'softcatala' ),
			array( 'status' => 401 )
		);
	}

	// 2. Verify WP REST nonce (X-WP-Nonce header or _wpnonce param).
	$nonce = $request->get_header( 'X-WP-Nonce' ) ?: $request->get_param( '_wpnonce' );

	if ( ! $nonce ) {
		return new WP_Error(
			'rest_missing_nonce',
			__( 'Falta el nonce. Incloeu la capçalera X-WP-Nonce o el paràmetre _wpnonce.', 'softcatala' ),
			array( 'status' => 403 )
		);
	}

	if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return new WP_Error(
			'rest_invalid_nonce',
			__( 'El nonce no és vàlid.', 'softcatala' ),
			array( 'status' => 403 )
		);
	}

	// 3. Must have permission to edit the specific post.
	$id = absint( $request->get_param( 'id' ) );
	if ( ! current_user_can( 'edit_post', $id ) ) {
		return new WP_Error(
			'rest_forbidden',
			__( 'No teniu permís per modificar aquesta tasca.', 'softcatala' ),
			array( 'status' => 403 )
		);
	}

	return true;
}

/**
 * Callback for the tasca PATCH endpoint. Updates the estat_tasca term.
 *
 * @param WP_REST_Request $request Full REST request.
 * @return WP_REST_Response|WP_Error Response on success; WP_Error on failure.
 */
function sc_rest_update_tasca_estat( $request ) {
	$id         = absint( $request->get_param( 'id' ) );
	$estat_slug = sanitize_text_field( $request->get_param( 'estat' ) );

	// Validate the post is a tasca.
	if ( 'tasca' !== get_post_type( $id ) ) {
		return new WP_Error(
			'rest_tasca_not_found',
			__( 'No s\'ha trobat cap tasca amb aquest ID.', 'softcatala' ),
			array( 'status' => 404 )
		);
	}

	// Validate the estat slug exists in the estat_tasca taxonomy.
	$term = get_term_by( 'slug', $estat_slug, 'estat_tasca' );
	if ( ! $term || is_wp_error( $term ) ) {
		return new WP_Error(
			'rest_estat_not_found',
			__( 'L\'estat indicat no existeix.', 'softcatala' ),
			array( 'status' => 422 )
		);
	}

	// Update the term assignment.
	$result = wp_set_post_terms( $id, array( $term->term_id ), 'estat_tasca' );
	if ( is_wp_error( $result ) ) {
		return new WP_Error(
			'rest_estat_update_failed',
			__( 'No s\'ha pogut actualitzar l\'estat de la tasca.', 'softcatala' ),
			array( 'status' => 500 )
		);
	}

	return new WP_REST_Response(
		array(
			'id'    => $id,
			'estat' => $estat_slug,
		),
		200
	);
}
