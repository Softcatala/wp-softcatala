<?php
/**
 * REST API endpoints for tasques.
 *
 * POST  sc/v1/tasca              — create a task with its ACF fields.
 * PATCH sc/v1/tasca/{id}/estat   — move a task between kanban columns.
 *
 * @package Softcatala
 */

/**
 * Register the tasques REST API endpoint.
 */
function sc_register_tasques_api() {
	sc_register_tasca_create_route();

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
 * CSRF protection is left to core rather than re-checked here. WordPress runs
 * rest_cookie_check_errors() on `rest_authentication_errors` (priority 100,
 * from WP_REST_Server::serve_request) before any route is dispatched, and it
 * demands a valid wp_rest nonce whenever — and only whenever — the request is
 * authenticated by a login cookie:
 *
 *   - cookie + valid nonce   → passes through to this callback
 *   - cookie + bad nonce     → 403 rest_cookie_invalid_nonce, never reaches us
 *   - cookie + no nonce      → wp_set_current_user( 0 ), so the check below 401s
 *   - Application Password   → no nonce demanded, this callback decides
 *
 * A hand-rolled nonce check on top of that would reject Application Password
 * clients, which have no session to mint a nonce from. Anonymous requests are
 * already stopped earlier by sc_only_allow_logged_in_rest_access().
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

	// 2. Must have permission to edit the specific post.
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

	// Track when a task enters a terminal state for the board cutoff filter.
	if ( get_term_meta( $term->term_id, 'is_terminal', true ) ) {
		update_post_meta( $id, '_terminal_date', current_time( 'mysql' ) );
	} else {
		delete_post_meta( $id, '_terminal_date' );
	}

	return new WP_REST_Response(
		array(
			'id'    => $id,
			'estat' => $estat_slug,
		),
		200
	);
}

/**
 * Register the POST sc/v1/tasca route for creating tasks.
 *
 * Split out from sc_register_tasques_api() only for readability; it is called
 * from there, not hooked separately.
 */
function sc_register_tasca_create_route() {
	register_rest_route(
		'sc/v1',
		'/tasca',
		array(
			'methods'             => 'POST',
			'callback'            => 'sc_rest_create_tasca',
			'permission_callback' => 'sc_rest_create_tasca_permissions',
			'args'                => array(
				'title'          => array(
					'description'       => 'Títol de la tasca.',
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'content'        => array(
					'description'       => 'Descripció de la tasca (HTML permès).',
					'type'              => 'string',
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'wp_kses_post',
				),
				'projecte'       => array(
					'description'       => 'ID del projecte al qual pertany la tasca.',
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'estat'          => array(
					'description'       => 'Slug de l\'estat (terme de la taxonomia estat_tasca). Si s\'omet, s\'assigna el primer estat no terminal del tauler.',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'milestone'      => array(
					'description'       => 'ID del milestone. Ha de pertànyer al projecte indicat.',
					'type'              => 'integer',
					'required'          => false,
					'sanitize_callback' => 'absint',
				),
				'responsables'   => array(
					'description' => 'IDs dels usuaris responsables de la tasca.',
					'type'        => 'array',
					'required'    => false,
					'items'       => array( 'type' => 'integer' ),
				),
				'data_venciment' => array(
					'description'       => 'Data de venciment en format Y-m-d.',
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'tasca_interna'  => array(
					'description' => 'Amaga la tasca als visitants anònims.',
					'type'        => 'boolean',
					'required'    => false,
					'default'     => false,
				),
				'etiquetes'      => array(
					'description' => 'Etiquetes (taxonomia tag_tasca). Les que no existeixin es crearan.',
					'type'        => 'array',
					'required'    => false,
					'items'       => array( 'type' => 'string' ),
				),
				'status'         => array(
					'description' => 'Estat de publicació del post.',
					'type'        => 'string',
					'required'    => false,
					'default'     => 'publish',
					'enum'        => array( 'publish', 'draft' ),
				),
			),
		)
	);
}

/**
 * Permission callback for the tasca POST endpoint.
 *
 * CSRF protection is left to core, for the reasons set out on
 * sc_rest_tasca_permissions().
 *
 * @param WP_REST_Request $request Full REST request.
 * @return true|WP_Error True if allowed; WP_Error otherwise.
 */
function sc_rest_create_tasca_permissions( $request ) {
	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_not_logged_in',
			__( 'Heu d\'iniciar sessió per crear tasques.', 'softcatala' ),
			array( 'status' => 401 )
		);
	}

	$post_type = get_post_type_object( 'tasca' );
	if ( ! $post_type ) {
		return new WP_Error(
			'rest_post_type_missing',
			__( 'El tipus de contingut «tasca» no està registrat.', 'softcatala' ),
			array( 'status' => 500 )
		);
	}

	if ( ! current_user_can( $post_type->cap->create_posts ) ) {
		return new WP_Error(
			'rest_forbidden',
			__( 'No teniu permís per crear tasques.', 'softcatala' ),
			array( 'status' => 403 )
		);
	}

	if ( 'publish' === $request->get_param( 'status' ) && ! current_user_can( $post_type->cap->publish_posts ) ) {
		return new WP_Error(
			'rest_cannot_publish',
			__( 'No teniu permís per publicar tasques.', 'softcatala' ),
			array( 'status' => 403 )
		);
	}

	return true;
}

/**
 * Resolve the estat_tasca term a new task should land in.
 *
 * An explicit slug wins. Otherwise the board's first non-terminal column (lowest
 * `order` term meta) is used, so tasks created by scripts show up at the head of
 * the board rather than in the logged-in-only "Sense columna" bucket.
 *
 * @param string $slug Requested estat slug, or an empty string for the default.
 * @return WP_Term|null|WP_Error Term on success; null when no estat could be
 *                               resolved and none was requested; WP_Error when
 *                               an explicit slug does not exist.
 */
function sc_rest_resolve_tasca_estat( $slug ) {
	if ( '' !== $slug ) {
		$term = get_term_by( 'slug', $slug, 'estat_tasca' );
		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error(
				'rest_estat_not_found',
				__( 'L\'estat indicat no existeix.', 'softcatala' ),
				array( 'status' => 422 )
			);
		}
		return $term;
	}

	foreach ( \Softcatala\Providers\Tasques::get_ordered_estats() as $estat ) {
		if ( ! $estat->is_terminal ) {
			return $estat;
		}
	}

	return null;
}

/**
 * Callback for the tasca POST endpoint. Creates a tasca with its ACF fields set.
 *
 * @param WP_REST_Request $request Full REST request.
 * @return WP_REST_Response|WP_Error Response on success; WP_Error on failure.
 */
function sc_rest_create_tasca( $request ) {
	$title       = trim( (string) $request->get_param( 'title' ) );
	$projecte_id = absint( $request->get_param( 'projecte' ) );

	if ( '' === $title ) {
		return new WP_Error(
			'rest_tasca_empty_title',
			__( 'El títol de la tasca no pot ser buit.', 'softcatala' ),
			array( 'status' => 422 )
		);
	}

	// The projecte_tasca ACF field is required — validate before creating anything.
	if ( 'projecte' !== get_post_type( $projecte_id ) ) {
		return new WP_Error(
			'rest_projecte_not_found',
			__( 'El projecte indicat no existeix.', 'softcatala' ),
			array( 'status' => 422 )
		);
	}

	$estat = sc_rest_resolve_tasca_estat( (string) $request->get_param( 'estat' ) );
	if ( is_wp_error( $estat ) ) {
		return $estat;
	}

	// A milestone must belong to the same projecte, mirroring the admin-side
	// restriction in sc_filter_milestone_tasca_by_projecte().
	$milestone_id = absint( $request->get_param( 'milestone' ) );
	if ( $milestone_id ) {
		if ( 'milestone' !== get_post_type( $milestone_id ) ) {
			return new WP_Error(
				'rest_milestone_not_found',
				__( 'El milestone indicat no existeix.', 'softcatala' ),
				array( 'status' => 422 )
			);
		}
		if ( (int) get_post_meta( $milestone_id, 'projecte_milestone', true ) !== $projecte_id ) {
			return new WP_Error(
				'rest_milestone_projecte_mismatch',
				__( 'El milestone indicat no pertany al projecte indicat.', 'softcatala' ),
				array( 'status' => 422 )
			);
		}
	}

	$responsables = array_map( 'absint', (array) $request->get_param( 'responsables' ) );
	$responsables = array_values( array_filter( $responsables ) );
	foreach ( $responsables as $user_id ) {
		if ( ! get_userdata( $user_id ) ) {
			return new WP_Error(
				'rest_responsable_not_found',
				sprintf(
					/* translators: %d: user ID */
					__( 'L\'usuari %d no existeix.', 'softcatala' ),
					$user_id
				),
				array( 'status' => 422 )
			);
		}
	}

	// ACF stores date_picker values as Ymd; accept ISO dates on the wire.
	$data_venciment = trim( (string) $request->get_param( 'data_venciment' ) );
	$venciment_acf  = '';
	if ( '' !== $data_venciment ) {
		$date = DateTime::createFromFormat( '!Y-m-d', $data_venciment );
		if ( ! $date || $date->format( 'Y-m-d' ) !== $data_venciment ) {
			return new WP_Error(
				'rest_invalid_data_venciment',
				__( 'La data de venciment ha de tenir el format Y-m-d.', 'softcatala' ),
				array( 'status' => 422 )
			);
		}
		$venciment_acf = $date->format( 'Ymd' );
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'tasca',
			'post_title'   => $title,
			'post_content' => (string) $request->get_param( 'content' ),
			'post_status'  => $request->get_param( 'status' ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return new WP_Error(
			'rest_tasca_create_failed',
			__( 'No s\'ha pogut crear la tasca.', 'softcatala' ),
			array( 'status' => 500 )
		);
	}

	update_field( 'field_projecte_tasca', $projecte_id, $post_id );
	update_field( 'field_tasca_interna', $request->get_param( 'tasca_interna' ) ? 1 : 0, $post_id );

	if ( $milestone_id ) {
		update_field( 'field_milestone_tasca', $milestone_id, $post_id );
	}
	if ( $responsables ) {
		update_field( 'field_responsable_tasca', $responsables, $post_id );
	}
	if ( '' !== $venciment_acf ) {
		update_field( 'field_data_venciment', $venciment_acf, $post_id );
	}

	if ( $estat ) {
		wp_set_post_terms( $post_id, array( $estat->term_id ), 'estat_tasca' );

		// Match the PATCH endpoint: terminal states carry the board cutoff stamp.
		if ( get_term_meta( $estat->term_id, 'is_terminal', true ) ) {
			update_post_meta( $post_id, '_terminal_date', current_time( 'mysql' ) );
		}
	}

	$etiquetes = array_filter( array_map( 'sanitize_text_field', (array) $request->get_param( 'etiquetes' ) ) );
	if ( $etiquetes ) {
		wp_set_post_terms( $post_id, array_values( $etiquetes ), 'tag_tasca' );
	}

	// save_post_tasca fired during wp_insert_post, before tasca_interna was
	// written, so the invalidation hook ran too early. Drop the transient again.
	delete_transient( \Softcatala\Providers\Tasques::TRANSIENT_INTERNAL_TASCA_IDS );

	$response = new WP_REST_Response(
		array(
			'id'             => $post_id,
			'title'          => get_the_title( $post_id ),
			'status'         => get_post_status( $post_id ),
			'estat'          => $estat ? $estat->slug : '',
			'projecte'       => $projecte_id,
			'milestone'      => $milestone_id,
			'responsables'   => $responsables,
			'data_venciment' => '' !== $data_venciment ? $data_venciment : '',
			'tasca_interna'  => (bool) $request->get_param( 'tasca_interna' ),
			'etiquetes'      => wp_get_post_terms( $post_id, 'tag_tasca', array( 'fields' => 'slugs' ) ),
			'link'           => get_permalink( $post_id ),
			'edit_link'      => get_edit_post_link( $post_id, 'url' ),
		),
		201
	);
	$response->header( 'Location', get_permalink( $post_id ) );

	return $response;
}
