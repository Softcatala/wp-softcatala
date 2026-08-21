<?php

/** APARELLS **/
add_action( 'wp_ajax_send_aparell', 'sc_send_aparell' );
add_action( 'wp_ajax_nopriv_send_aparell', 'sc_send_aparell' );
/** PROGRAMES **/
add_action( 'wp_ajax_send_vote', 'sc_send_vote' );
add_action( 'wp_ajax_nopriv_send_vote', 'sc_send_vote' );
add_action( 'wp_ajax_search_program', 'sc_search_program' );
add_action( 'wp_ajax_nopriv_search_program', 'sc_search_program' );
add_action( 'wp_ajax_add_new_program', 'sc_add_new_program' );
add_action( 'wp_ajax_nopriv_add_new_program', 'sc_add_new_program' );
add_action( 'wp_ajax_add_new_baixada', 'sc_add_new_baixada' );
add_action( 'wp_ajax_nopriv_add_new_baixada', 'sc_add_new_baixada' );
/** CONTACT FORM **/
add_action( 'wp_ajax_contact_form', 'sc_contact_form' );
add_action( 'wp_ajax_nopriv_contact_form', 'sc_contact_form' );
/** SINÒNIMS **/
add_action( 'wp_ajax_find_sinonim', 'sc_find_sinonim' );
add_action( 'wp_ajax_nopriv_find_sinonim', 'sc_find_sinonim' );
/** PROJECTES */
add_action( 'wp_ajax_subscribe_list', 'sc_subscribe_list' );
add_action( 'wp_ajax_nopriv_subscribe_list', 'sc_subscribe_list' );
/** APARELLS DATA LOAD */
add_action( 'wp_ajax_aparell_ajax_load', 'sc_aparell_ajax_load' );
add_action( 'wp_ajax_nopriv_aparell_ajax_load', 'sc_aparell_ajax_load' );

/** CONJUGADOR  */
add_action( 'wp_ajax_conjugador_search', 'sc_conjugador_search' );
add_action( 'wp_ajax_nopriv_conjugador_search', 'sc_conjugador_search' );

/** DICCIONARI ENG-CAT  */
add_action( 'wp_ajax_diccionari_engcat_search', 'sc_diccionari_engcat_search' );
add_action( 'wp_ajax_nopriv_diccionari_engcat_search', 'sc_diccionari_engcat_search' );

/**
 * Retrieves the information from a given aparell
 *
 * @return json response
 */
function sc_aparell_ajax_load() {
	$aparell_id = intval( sanitize_text_field( $_POST["aparell_id"] ) );
	$post       = Timber::get_post( $aparell_id );

	$result['aparell_id']     = $aparell_id;
	$result['aparell_detall'] = Timber::fetch( 'ajax/aparell-detall.twig', array( 'post' => $post ) );

	wp_send_json( $result );
}

/**
 * Function to make the request to synonims dictionary server
 *
 * @return json response
 */
function sc_find_sinonim() {
	if ( ! isset( $_POST["paraula"] ) ) {
		$result = new SC_SinonimsResult( 500, "S'ha produït un error en contactar amb el servidor. Proveu una altra vegada." );
	} else {
		$paraula = str_replace("'", '’', stripslashes( sanitize_text_field( $_POST["paraula"] ) ) );

		$sinonims = new SC_Sinonims();

		$result = $sinonims->get_paraula( $paraula );
	}

	wp_send_json( $result, (int) $result->status );
}

/**
 *
 * @return json response
 */
function sc_conjugador_search() {
	if ( ! isset( $_POST["verb"] ) ) {
		$result = new SC_SingleResult( 500, 'S\'ha produït un error en contactar amb el servidor. Proveu una altra vegada.' );
	} else {
		$verb = sanitize_text_field( $_POST["verb"] );
		$infinitiu = sanitize_text_field( $_POST["infinitiu"] );
		$url = sanitize_text_field( $_POST["url"] );
		$ajaxquery = sanitize_text_field( $_POST["ajaxquery"] );
		$conjugador = new SC_Conjugador();
		$result = $conjugador->get_verb( $verb, $infinitiu, $url, $ajaxquery );
	}

	wp_send_json( $result, (int) $result->status );
	
}

/**
 * Retrieves the results from the DICCIONARI ENG-CAT
 *
 * @return json response
 */
function sc_diccionari_engcat_search() {
	
	if ( ! isset( $_POST["paraula"] ) || ! isset( $_POST["llengua"] ) ) {
		$result = new SC_Diccionari_EngCatResult( 500, 'S\'ha produït un error en contactar amb el servidor. Proveu una altra vegada.', '' );
	} else {
		$paraula = sanitize_text_field( $_POST["paraula"] );
		$llengua = sanitize_text_field( $_POST["llengua"] );

		if ( $llengua !== 'cat' && $llengua !== 'eng' ) {
			$result = new SC_Diccionari_EngCatResult(
				400,
				'Codi de llengua no vàlid. Només es permet "cat" o "eng".'
			);
			} elseif ( empty( $paraula ) ) {
			$result = new SC_Diccionari_EngCatResult(
				400,
				'La paraula no pot estar buida.'
			);
		} else {
			$diccionari = new SC_Diccionari_engcat();
			$result = $diccionari->get_paraula( $paraula, $llengua );
    	}
	}

	wp_send_json( $result, (int) $result->status );
}


/**
 * Function to prepare mailman URLs for inter-LXC connectivity
 */
function prepare_mailman_url ( $llista ) {

	if ( $_SERVER['SERVER_NAME'] == 'www.softcatala.org' ) {
		$llista = str_replace( 'https://llistes.softcatala.org/', 'http://mail.scnet/', $llista);
	}

	return $llista;
}

/**
 * Function to make the request to synonims dictionary server
 *
 * @return json response
 */
function sc_subscribe_list() {
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] ) ) {
		$result['text'] = "S'ha produït un error. Proveu més tard.";
	} else {
		$nom           = sanitize_text_field( $_POST["nom"] );
		$correu        = sanitize_text_field( $_POST["correu"] );
		$llista        = sanitize_text_field( $_POST["llista"] );
		$llista        = prepare_mailman_url( $llista );
		$projecte      = sanitize_text_field( $_POST["projecte"] );
		$projecte_slug = sanitize_text_field( $_POST["projecte_slug"] );

		if ( ! empty ( $llista ) ) {
			$password = get_option( 'llistes_access' );
			if ( ! empty ( $password ) ) {
				$path                  = '/members/add?subscribe_or_invite=0&send_welcome_msg_to_this_batch=1&notification_to_list_owner=0&subscribees_upload=' . urlencode( $correu ) . '&adminpw=' . $password;
				$list_admin_url        = str_replace( 'listinfo', 'admin', $llista );
				$url                   = $list_admin_url . $path;
				$response_subscription = send_subscription_to_mailinglist( $url );
				if ( $response_subscription['status'] ) {
					$result['text'] = 'Gràcies per subscriure-vos a la llista. Ara heu de rebre un email de confirmació.';
				} else {
					$result['text'] = "S'ha produït un error. " . $response_subscription['message'];
				}
			}
		} else {
			$to_email = 'web@softcatala.org';
			$subject  = '[Projectes] Demanda de participació al projecte ' . $projecte;
			$message  = 'Un usuari ha demanat col·laborar al projecte ' . $projecte;
			$message .= '<br/><br/>Atès que aquest projecte no té llista de correu, possiblement caldrà contactar l\'usuari';
			$message .= '<br/><br/><strong>Dades de l\'usuari</strong><br/><br/>Nom: ' . $nom . '<br/>Email: ' . $correu;

			//proceed with PHP email.
			$headers   = array();
			$headers[] = 'From: ' . $nom . ' <' . $to_email . '>';
			$headers[] = 'Reply-To: ' . $correu;
			$headers[] = 'X-Mailer: PHP/' . phpversion();
			$headers[] = 'Content-Type: text/html';

			// if project has responsables email them too
			$projecte     = \Softcatala\Posts\Projecte::find_by_slug( $projecte_slug );
			$responsables = $projecte ? $projecte->responsables() : false;
			if ( $responsables ) {
				foreach ( $responsables as $user ) {
					$to_email = $to_email . ',' . $user['user_email'];
				}
			}

			if ( wp_mail( $to_email, $subject, $message, $headers ) ) {
				$result['text'] = "Gràcies pel vostre interès. Ens posarem en contacte amb vosaltres aviat.";
			} else {
				$result['text'] = "S'ha produït un error. Proveu més tard.";
			}
		}
	}

	wp_send_json( $result );
}

/**
 * Function to send a contact form
 *
 * @return json response
 */
function sc_contact_form() {
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] ) ) {
		wp_send_json( array( 'type' => 'error', 'text' => 'S\'ha produït un error en enviar el formulari.' ) );

		return;
	}

	$to_email   = sanitize_text_field( $_POST["to_email"] );
	$from_email = isset( $_POST["from_email"] ) ? sanitize_text_field( $_POST["from_email"] ) : $to_email;
	$nom_from   = sanitize_text_field( $_POST["nom_from"] );
	$assumpte   = sanitize_text_field( $_POST["assumpte"] );

	//check if its an ajax request, exit if not
	if ( ! isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) || strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) != 'xmlhttprequest' ) {
		wp_send_json( array( //create JSON data
			'type' => 'error',
			'text' => 'Sorry Request must be Ajax POST'
		) );
	}

	//Sanitize input data using PHP filter_var().
	$nom       = sanitize_text_field( $_POST["nom"] );
	$correu    = sanitize_email( $_POST["correu"] );
	$tipus     = isset( $_POST["tipus"] ) ? sanitize_text_field( $_POST["tipus"] ) : '';
	$comentari = stripslashes( sanitize_text_field( ( $_POST["comentari"] ) ) );

	// Identifies which of the three forms sharing this endpoint sent the request.
	// Absent on HTML served from a page cache predating this field: stay permissive.
	$form_id = isset( $_POST["form_id"] ) ? sanitize_key( $_POST["form_id"] ) : '';

	// Honeypot: a field hidden from humans, so any value means an automated filler.
	$honeypot_validation = sc_validate_honeypot();
	if ( $honeypot_validation !== true ) {
		sc_contact_form_reject( $honeypot_validation, $nom, $correu, $comentari );
	}

	// Validate HTTP headers - check for User-Agent and Referer
	$header_validation = sc_validate_http_headers();
	if ( $header_validation !== true ) {
		sc_contact_form_reject( $header_validation, $nom, $correu, $comentari );
	}

	// The message type must be one the sending form actually offers
	$tipus_validation = sc_validate_tipus( $tipus, $form_id );
	if ( $tipus_validation !== true ) {
		sc_contact_form_reject( $tipus_validation, $nom, $correu, $comentari );
	}

	// Validate the sender's name. Skipped on the anonymous form, which invites pseudonyms.
	if ( 'anonim' !== $form_id && '' !== $form_id ) {
		$name_validation = sc_validate_name( $nom );
		if ( $name_validation !== true ) {
			sc_contact_form_reject( $name_validation, $nom, $correu, $comentari );
		}
	}

	// Validate email format and check that the address could actually receive a reply
	$email_validation = sc_validate_email( $correu );
	if ( $email_validation !== true ) {
		sc_contact_form_reject( $email_validation, $nom, $correu, $comentari );
	}

	// Validate message content against spam patterns
	$spam_validation = sc_validate_message_content( $comentari, $correu );
	if ( $spam_validation !== true ) {
		sc_contact_form_reject( $spam_validation, $nom, $correu, $comentari );
	}

	//email body
	$message_body = "Tipus: " . $tipus . "\r\n\rComentari: " . $comentari . "\r\n\rNom: " . $nom . "\r\nCorreu electrònic: " . $correu;

	//proceed with PHP email.
	$headers = 'From: ' . $nom_from . ' <' . $from_email . ">\r\n" .
	           'Reply-To: ' . $correu . '' . "\r\n" .
	           'X-Mailer: PHP/' . phpversion();

	$send_mail = wp_mail( $to_email, $assumpte, $message_body, $headers );

	if ( ! $send_mail ) {
		//If mail couldn't be sent output error. Check your PHP email configuration (if it ever happens)
		wp_send_json( array( 'type' => 'error', 'text' => 'S\'ha produït un error en enviar el formulari.' ) );
	} else {
		wp_send_json( array(
			'type' => 'message',
			'text' => $nom . ', et donem les gràcies per ajudar-nos a millorar el nostre lloc web.'
		) );
	}
}

/**
 * Function to add a download related to a program
 *
 * @return json response
 */
function sc_add_new_baixada() {
	$return = array();
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] ) ) {
		$return['status'] = 0;
		$return['text']   = "S'ha produït un error en enviar les dades. Torneu a carregar la pàgina i proveu-ho una altra vegada.";
	} else {
		$baixades    = json_decode( stripslashes( $_POST["baixades"] ) );
		$programa_id = sanitize_text_field( $_POST["programa_id"] );
		$taxonomy    = 'sistema-operatiu-programa';

		//Related downloads
		$version_info = array();
		$terms        = array();
		foreach ( $baixades as $key => $baixada ) {
			$version_info[ $key ]['download_url']     = $baixada->url;
			$version_info[ $key ]['download_version'] = $baixada->versio;
			$version_info[ $key ]['download_size']    = '';
			$version_info[ $key ]['arquitectura']     = $baixada->arquitectura;
			$version_info[ $key ]['download_os']      = map_so( $baixada->sistema_operatiu );
			$terms[]                                  = $baixada->sistema_operatiu;
		}

		$field_key = acf_get_field_key( 'baixada', $programa_id );
		update_field( $field_key, $version_info, $programa_id );

		wp_set_post_terms( $programa_id, $terms, $taxonomy );

		$return['status'] = 1;
	}

	wp_send_json( $return );
}

/**
 * Function to add a new draft program into database
 *
 * @return json response
 */
function sc_add_new_program() {
	$return = array();
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] ) ) {
		$return['status'] = 0;
		$return['text']   = "S'ha produït un error en enviar les dades. Torneu a carregar la pàgina i proveu-ho una altra vegada.";
	} else {
		$nom                = sanitize_text_field( $_POST["nom"] );
		$email_usuari       = sanitize_email( $_POST["email_usuari"] );
		$comentari_usuari   = sanitize_text_field( $_POST["comentari_usuari"] );
		$descripcio         = sanitize_text_field( $_POST["descripcio"] );
		$autor_programa     = sanitize_text_field( $_POST["autor_programa"] );
		$lloc_web_programa  = sanitize_text_field( $_POST["lloc_web_programa"] );
		// The form sent the licence as `tipus` until this was fixed, so pages still
		// served from the cache with the previous script keep working.
		$llicencia          = sanitize_text_field( $_POST["llicencia"] ?? $_POST["tipus"] ?? '' );
		$autor_traduccio    = sanitize_text_field( $_POST["autor_traduccio"] ?? '' );
		$categoria_programa = sanitize_text_field( $_POST["categoria_programa"] );
		$slug               = sanitize_title_with_dashes( $nom );

		$terms = array(
			'categoria-programa' => array( $categoria_programa )
		);

		if ( ! empty( $llicencia ) ) {
			$terms['llicencia'] = array( $llicencia );
		}

		$metadata = array(
			'autor_programa'    => $autor_programa,
			'lloc_web_programa' => $lloc_web_programa,
			'autor_traduccio'   => $autor_traduccio
		);

		$return = sc_add_draft_content( 'programa', $nom, $descripcio, $slug, $terms, $metadata );

		if ( $return['status'] == 1 ) {
			//Logo and screenshot file upload
			$logo_attach_id       = sc_upload_file( 'logo', $return['post_id'] );
			$screenshot_attach_id = sc_upload_file( 'captura', $return['post_id'] );
			$metadata             = array(
				'logotip_programa'   => $logo_attach_id,
				'imatge_destacada_1' => $screenshot_attach_id
			);
			sc_update_metadata_acf( $return['post_id'], $metadata );

			$from_email = get_option( 'email_rebost' );
			$to_email   = get_option( 'to_email_rebost' );
			$nom_from   = "Programes i aplicacions de Softcatalà";
			$assumpte   = "[Programes] Programa enviat per formulari";

			$fields = array(
				"Nom del programa"      => $nom,
				"Descripció"            => $descripcio,
				"Comentari de l'usuari" => $comentari_usuari,
				"Email de l'usuari"     => $email_usuari,
				"URL Dashboard"         => admin_url( "post.php?post=" . $return['post_id'] . "&action=edit" )
			);
			sendEmailWithFromAndTo( $to_email, $from_email, $nom_from, $assumpte, $fields );
		}
	}

	wp_send_json( $return );
}

/**
 * Function to look up a program with a title similar to the title from the search on the add program form
 *
 * @return json response
 */
function sc_search_program() {
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] ) ) {
		$result['text'] = "S'ha produït un error en cercar el programa. Podeu continuar igualment.";
	} else {
		sc_check_is_ajax_call();

		$nom_programa = sanitize_text_field( $_POST["nom_programa"] );

		$result = array();
		// get_sorted() returns a Timber post collection rather than an array, so the
		// posts are handed to the template as they come and counted through Countable.
		$programs = array();
		if ( ! empty ( $nom_programa ) ) {
			$query['s'] = $nom_programa;
			$programs   = Softcatala\Providers\Programes::get_sorted( $query );
		}

		if ( count( $programs ) > 0 ) {
			$result['programs'] = Timber::fetch( 'ajax/programs-list.twig', array( 'programs' => $programs ) );
			$result['text']     = "El programa que proposeu és algun dels que es mostren a continuació?";
		} else {
			$result['text'] = "El programa no està a la nostra base de dades. Podeu continuar!";
		}
	}

	wp_send_json( $result );
}

/**
 * This function increments the vote count for a 'programa' post type and calculates
 * the new rate
 *
 * @return json response
 */
function sc_send_vote() {
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] ) ) {
		$return['text'] = "No s'ha pogut enviar el vot. Proveu més tard.";
	} else {
		sc_check_is_ajax_call();

		$post_id = intval( sanitize_text_field( $_POST["post_id"] ) );

		$result = false;
		if ( $post_id ) {

			$rate    = sanitize_text_field( $_POST["rate"] );
			$single  = true;

			$current_rating = get_post_meta( $post_id, 'valoracio', $single );
			$votes          = get_post_meta( $post_id, 'vots', $single );

			$new_votes = $votes + 1;
			$new_rate  = $current_rating * ( $votes / $new_votes ) + $rate * ( 1 / $new_votes );

			$new_rate = number_format( (float) $new_rate, 2, '.', '' );

			update_field( 'valoracio', $new_rate, $post_id );
			update_field( 'vots', $new_votes, $post_id );

			if ( class_exists('\rtCamp\WP\Nginx\Purger' ) ){
				$purger = new \rtCamp\WP\Nginx\Purger();

				$purger->purgeUrl( get_permalink( $post_id ) );
			}

			$result = true;
		}

		if ( ! $result ) {
			$return['status'] = 0;
			$return['text']   = "No s'ha pogut enviar el vot. Proveu més tard.";
		} else {
			$return['status']    = 1;
			$return['cookie_id'] = sanitize_text_field( $_POST["cookie_id"] );
			$return['text']      = "Gràcies per enviar-nos la vostra valoració!";
			$return['vots']      = $new_votes;
			$return['valoracio'] = number_format( (float) $new_rate, 2, ',', '.' );
			;
		}
	}

	wp_send_json( $return );
}

/**
 * Creates a new post of the type 'aparell' using the data sent from the form ($_POST)
 *
 * @return json response
 */
function sc_send_aparell() {
	$return = array();
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] ) ) {
		$return['status'] = 0;
	} else {
		sc_check_is_ajax_call();

		$nom              = sanitize_text_field( $_POST["nom"] );
		$tipus_aparell    = sanitize_text_field( $_POST["tipus_aparell"] );
		$fabricant        = sanitize_text_field( $_POST["fabricant"] );
		$sistema_operatiu = sanitize_text_field( $_POST["sistema_operatiu"] );
		$versio           = sanitize_text_field( $_POST["versio"] );
		$traduccio_catala = sanitize_text_field( $_POST["traduccio_catala"] );
		$correccio_catala = sanitize_text_field( $_POST["correccio_catala"] );

		$comentari = stripslashes( sanitize_text_field( $_POST["comentari"] ) );

		$sc_aparell = new SC_Aparell( $nom, $tipus_aparell, $fabricant, $sistema_operatiu, $versio, $traduccio_catala, $correccio_catala );

		if ( $sc_aparell->is_draft() ) {

			$from_email = get_option( 'email_rebost' );
			$to_email   = get_option( 'to_email_rebost' );
			$nom_from   = "Aparells de Softcatalà";
			$assumpte   = "[Aparells] Aparell enviat per formulari";

			$fields = array(
				"Nom de l'aparell" => $sc_aparell->get_nom(),
				"Comentari"        => $comentari,
				"URL Dashboard"    => admin_url( "post.php?post=" . $sc_aparell->get_id() . "&action=edit" )
			);
			sendEmailWithFromAndTo( $to_email, $from_email, $nom_from, $assumpte, $fields );
		}

		$return = $sc_aparell->get_return();
	}

	wp_send_json( $return );
}

/**
 * Creates the post based on the basic information provided
 *
 *
 * @param $type
 * @param $nom
 * @param $descripcio
 * @param $slug
 * @param $allTerms
 * @param $metadata
 *
 * @return array|mixed|void
 */
function sc_add_draft_content( $type, $nom, $descripcio, $slug, $allTerms, $metadata ) {
	$return = array();
	if ( isset( $metadata['post_id'] ) ) {
		$parent_id = $metadata['post_id'];
		unset( $metadata['post_id'] );
		$post_status = 'publish';
	} else {
		$post_status = 'pending';
	}

	//Generate array data
	$post_data = array(
		'post_type'      => $type,
		'post_status'    => $post_status,
		'comment_status' => 'open',
		'ping_status'    => 'closed',
		'post_author'    => get_current_user_id(),
		'post_name'      => $slug,
		'post_title'     => $nom,
		'post_content'   => $descripcio,
		'post_date'      => date( 'Y-m-d H:i:s' )
	);

	$post_id = wp_insert_post( $post_data );
	if ( $post_id ) {

		foreach ( $allTerms as $taxonomy => $terms ) {
			wp_set_post_terms( $post_id, $terms, $taxonomy );
		}

		sc_update_metadata_acf( $post_id, $metadata );

		if ( $type == 'aparell' ) {
			$featured_image_attach_id = sc_upload_file( 'file', $post_id );
			if ( $featured_image_attach_id ) {
				$return = sc_set_featured_image( $post_id, $featured_image_attach_id );
			} else {
				$return['status'] = 1;
			}
		} else {
			$return['status'] = 1;
		}

	} else {
		$return['status'] = 0;
		$return['text']   = "S'ha produït un error en enviar les dades. Proveu una altra vegada.";
	}

	if ( $return['status'] == 1 ) {
		$return['post_id'] = $post_id;
		$return['text']    = 'Gràcies per enviar aquesta informació. La publicarem tan aviat com puguem.';
	}

	return $return;
}

/**
 * This funcions uploads a file to the wordpress media library
 *
 * @param $value
 * @param $post_id
 *
 * @return bool|int
 */
function sc_upload_file( $value, $post_id ) {
	if ( isset( $_FILES[ $value ] ) ) {
		$tmpfile = $_FILES[ $value ];

		$upload_overrides = array( 'test_form' => false );

		$uploaded = wp_handle_upload( $tmpfile, $upload_overrides );

		if ( $uploaded && ! isset( $uploaded['error'] ) ) {

			$wp_filetype = wp_check_filetype( basename( $uploaded['file'] ), null );

			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => preg_replace( '/.[^.]+$/', '', basename( $uploaded['file'] ) ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);

			$attach_id = wp_insert_attachment( $attachment, $uploaded['file'], $post_id );

			$attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded['file'] );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			return $attach_id;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Sets the featured image for a specific post
 *
 * @param $post_id
 * @param $attach_id
 *
 * @return mixed
 */
function sc_set_featured_image( $post_id, $attach_id ) {
	if ( $attach_id ) {
		set_post_thumbnail( $post_id, $attach_id );
		$return['status'] = 1;
	} else {
		$return['status'] = 0;
		$return['text']   = "S'ha produït un error en pujar la imatge. Proveu una altra vegada.";
	}

	return $return;
}

/** General **/
/**
 * Turns away anything that did not arrive as a same-origin XHR.
 *
 * Every call site verifies the nonce first, which is what actually stops CSRF,
 * so this only filters automated posts that replay an action name without
 * bothering to set the header. Browsers cannot attach X-Requested-With
 * cross-origin: it is not CORS-safelisted, so the attempt needs a preflight
 * that admin-ajax never approves.
 *
 * Answers 403 rather than a 200 carrying an error string, so a client that
 * stops sending the header — as the fetch() port of programes.js did — fails
 * in the log and the network tab instead of rendering English into a Catalan
 * page.
 */
function sc_check_is_ajax_call() {
	if ( ! isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) || strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) !== 'xmlhttprequest' ) {
		wp_send_json_error( array( 'text' => 'Request must be an AJAX POST' ), 403 );
	}
}

/**
 * This function updates an array of given post metadata
 *
 * @param int $post_id
 * @param array $metadata
 *
 * @return boolean
 */
function sc_update_metadata_acf( $post_id, $metadata ) {
	$result = false;
	if ( $post_id ) {

		foreach ( $metadata as $meta_key => $meta_value ) {
			update_field( $meta_key, $meta_value, $post_id );
		}
		$result = true;
	}

	return $result;
}

/**
 * Gets the field key from a field_name
 */
function acf_get_field_key( $field_name, $post_id ) {
	global $wpdb;
	$acf_fields = $wpdb->get_results( $wpdb->prepare( "SELECT ID,post_parent,post_name FROM $wpdb->posts WHERE post_excerpt=%s AND post_type=%s", $field_name, 'acf-field' ) );
	// get all fields with that name.
	switch ( count( $acf_fields ) ) {
		case 0: // no such field
			return false;
		case 1: // just one result.
			return $acf_fields[0]->post_name;
	}
	// result is ambiguous
	// get IDs of all field groups for this post
	$field_groups_ids = array();
	$field_groups     = acf_get_field_groups( array(
		'post_id' => $post_id,
	) );
	foreach ( $field_groups as $field_group ) {
		$field_groups_ids[] = $field_group['ID'];
	}

	// Check if field is part of one of the field groups
	// Return the first one.
	foreach ( $acf_fields as $acf_field ) {
		if ( in_array( $acf_field->post_parent, $field_groups_ids, true ) ) {
			return $acf_fields[0]->post_name;
		}
	}

	return false;
}

/**
 * Maps the category so ID with the program so value
 */
function map_so( $so_id ) {
	switch ( $so_id ) {
		case '67':
			$value = 'android';
			break;
		case '62':
			$value = 'ios';
			break;
		case '64':
			$value = 'linux';
			break;
		case '141':
			$value = 'multiplataforma';
			break;
		case '65':
			$value = 'osx';
			break;
		case '96':
			$value = 'web';
			break;
		case '59':
			$value = 'windows';
			break;
		case '140':
			$value = 'windows_phone';
			break;
		default:
			$value = '';
			break;
	}

	return $value;
}

/**
 * Ends a rejected contact form request.
 *
 * Answers with the same message a successful submission gets, so an automated
 * sender learns nothing about which check caught it. The `sc_contact_form_rejected`
 * action carries the real reason, so rejections can be logged and reviewed for
 * false positives without changing what the sender sees.
 *
 * @param string $reason Human readable reason the submission was rejected
 * @param string $nom The sender's name
 * @param string $correu The sender's email address
 * @param string $comentari The message content
 *
 * @return void This function does not return; wp_send_json() ends the request
 */
function sc_contact_form_reject( $reason, $nom, $correu, $comentari ) {
	do_action( 'sc_contact_form_rejected', $reason, array(
		'nom'       => $nom,
		'correu'    => $correu,
		'comentari' => $comentari,
		'ip'        => isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '',
	) );

	wp_send_json( array(
		'type' => 'message',
		'text' => $nom . ', et donem les gràcies per ajudar-nos a millorar el nostre lloc web.'
	) );
}

/**
 * Checks the honeypot field.
 *
 * The field is hidden with CSS and carries a "leave this blank" label for anyone
 * whose stylesheet failed to load, so a non-empty value means the form was filled
 * by something that does not render the page.
 *
 * @return bool|string True if valid, error message string if the honeypot was filled
 */
function sc_validate_honeypot() {
	if ( ! empty( $_POST['lloc_web'] ) ) {
		return 'Camp de verificació emplenat (honeypot).';
	}

	return true;
}

/**
 * Validates the message type against the options the sending form offers.
 *
 * Each form exposes its own set, so the check is per form. An unknown or absent
 * form id means the page was served from a cache predating the form_id field:
 * accept it rather than silently dropping a real message.
 *
 * @param string $tipus The submitted message type
 * @param string $form_id Identifier of the form that sent the request
 *
 * @return bool|string True if valid, error message string if not offered by the form
 */
function sc_validate_tipus( $tipus, $form_id ) {
	$allowed = array(
		'report'   => array( 'millora', 'error', 'general' ),
		'contacte' => array( 'general', 'noticies', 'publicitat', 'forums' ),
		'anonim'   => array( '' ),
	);

	if ( ! isset( $allowed[ $form_id ] ) ) {
		return true;
	}

	if ( ! in_array( $tipus, $allowed[ $form_id ], true ) ) {
		return 'Tipus de missatge no ofert pel formulari.';
	}

	return true;
}

/**
 * Validates the sender's name.
 *
 * Not called for the anonymous form, whose whole point is that the name need not
 * be real. The vowel test only applies to Latin script names of four letters or
 * more, so initials and names in other writing systems are left alone.
 *
 * @param string $nom The submitted name
 *
 * @return bool|string True if valid, error message string if it does not look like a name
 */
function sc_validate_name( $nom ) {
	$nom = trim( $nom );

	if ( '' === $nom ) {
		return 'El nom és buit.';
	}

	if ( preg_match_all( '/./u', $nom ) > 100 ) {
		return 'El nom és massa llarg.';
	}

	// Web or email addresses in a name field are a spam signature, never a real name
	if ( preg_match( '#https?://|www\.|@#i', $nom ) ) {
		return 'El nom conté una adreça web o de correu.';
	}

	// Only judge names written entirely in Latin script; other writing systems
	// have no Latin vowels and would be rejected wholesale.
	if ( ! preg_match( '/^[\p{Latin}\p{Zs}\'\.\-\x{00B7}]+$/u', $nom ) ) {
		return true;
	}

	$lletres = preg_replace( '/[^\p{Latin}]/u', '', $nom );
	if ( preg_match_all( '/./u', $lletres ) < 4 ) {
		return true;
	}

	if ( ! preg_match( '/[aeiouyàáâãäåæèéêëìíîïòóôõöøœùúûüýÿ]/iu', $lletres ) ) {
		return 'El nom no conté cap vocal.';
	}

	return true;
}

/**
 * Validates message content against spam patterns
 *
 * @param string $comentari The message content to validate
 * @param string $correu The sender's email address
 *
 * @return bool|string True if valid, error message string if spam detected
 */
function sc_validate_message_content( $comentari, $correu ) {
	$comentari = trim( $comentari );
	$comment_length = strlen( $comentari );

	if ( $comment_length < 10 ) {
		return 'El missatge és massa curt (mínim 10 caràcters).';
	}

	// Check if comment contains only a single word
	$words = preg_split( '/\s+/', $comentari, -1, PREG_SPLIT_NO_EMPTY );
	if ( count( $words ) < 2 ) {
		return 'El missatge ha de contenir almenys dues paraules.';
	}

	if ( preg_match( '/(.)\1{2,}/', $comentari ) ) {
		return 'El missatge conté caràcters repetits sospitosos.';
	}

	$caps_count = preg_match_all( '/[A-Z]/', $comentari );
	$alpha_count = preg_match_all( '/[a-zA-Z]/i', $comentari );
	if ( $alpha_count > 0 && ( $caps_count / $alpha_count ) > 0.5 ) {
		return 'El missatge conté massa majúscules.';
	}

	$emails_in_message = array();
	if ( preg_match_all( '/[\w\.\-]+@[\w\.\-]+\.\w+/', $comentari, $matches ) ) {
		$emails_in_message = $matches[0];
		// Allow only if it matches the sender's email exactly (e.g., in a reply context)
		if ( count( $emails_in_message ) > 0 ) {
			$has_other_email = false;
			foreach ( $emails_in_message as $email ) {
				if ( $email !== $correu ) {
					$has_other_email = true;
					break;
				}
			}
			if ( $has_other_email ) {
				return 'El missatge conté adreces de correu sospitoses.';
			}
		}
	}

	$punct_count = preg_match_all( '/[!?.,;:\-]/', $comentari );
	if ( $punct_count / $comment_length > 0.3 ) {
		return 'El missatge conté massa símbols de puntuació.';
	}

	return true;
}

/**
 * Validates the sender's email address.
 *
 * The address is what Softcatalà replies to, so the test is whether a reply could
 * ever arrive: correct syntax, not a documentation or test domain, not a throwaway
 * inbox, and a domain that resolves for mail.
 *
 * @param string $email The email address to validate
 *
 * @return bool|string True if valid, error message string if invalid
 */
function sc_validate_email( $email ) {
	if ( ! is_email( $email ) ) {
		return 'Adreça de correu electrònic no vàlida.';
	}

	// Extract domain from email
	$email_domain = strtolower( substr( strrchr( $email, '@' ), 1 ) );

	// Domains and TLDs reserved for documentation and testing (RFC 2606, RFC 6761).
	// No one can receive mail at these, so rejecting them costs no real messages.
	$reserved_domains = array(
		'example.com',
		'example.net',
		'example.org',
		'example.edu',
		'localhost',
	);

	if ( in_array( $email_domain, $reserved_domains, true ) ) {
		return 'Aquesta adreça de correu no pot rebre missatges.';
	}

	if ( preg_match( '/\.(test|example|invalid|localhost|local)$/', $email_domain ) ) {
		return 'Aquesta adreça de correu no pot rebre missatges.';
	}

	// List of disposable/temporary email domain providers
	$disposable_domains = array(
		'tempmail.com',
		'10minutemail.com',
		'guerrillamail.com',
		'mailinator.com',
		'maildrop.cc',
		'throwaway.email',
		'temp-mail.org',
		'yopmail.com',
		'fakeinbox.com',
		'trash-mail.com',
		'mailnesia.com',
		'tempmail.co',
		'sharklasers.com',
		'temp-mail.io',
		'testmail.net',
		'ethereal.email',
		'spam4.me',
		'grr.la',
		'guerrillamail.info',
		'guerrillamail.net',
		'guerrillamail.org',
		'pokemail.net',
		'spambox.us',
		'trashmail.com',
	);

	// Check if domain is in disposable list
	if ( in_array( $email_domain, $disposable_domains, true ) ) {
		return 'Aquesta adreça de correu no és permesa.';
	}

	if ( ! sc_domain_accepts_mail( $email_domain ) ) {
		return 'El domini del correu no accepta missatges.';
	}

	return true;
}

/**
 * Checks whether a domain resolves for mail delivery.
 *
 * Falls back to A and AAAA records because a host without an MX record is still a
 * valid mail destination (RFC 5321). Results are cached, and any lookup failure is
 * treated as deliverable so a DNS outage never swallows real messages.
 *
 * @param string $domain The domain part of an email address
 *
 * @return bool True if the domain resolves for mail, or if the lookup could not run
 */
function sc_domain_accepts_mail( $domain ) {
	if ( ! function_exists( 'checkdnsrr' ) ) {
		return true;
	}

	$cache_key = 'sc_mx_' . md5( $domain );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return ( '1' === $cached );
	}

	$resolves = checkdnsrr( $domain, 'MX' )
		|| checkdnsrr( $domain, 'A' )
		|| checkdnsrr( $domain, 'AAAA' );

	// Cache a negative result briefly: it may be a transient resolver failure
	set_transient( $cache_key, $resolves ? '1' : '0', $resolves ? WEEK_IN_SECONDS : HOUR_IN_SECONDS );

	return $resolves;
}

/**
 * Validates HTTP headers to prevent direct POST attacks
 *
 * @return bool|string True if valid, error message string if headers missing
 */
function sc_validate_http_headers() {
	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return 'Sol·licitud no vàlida (User-Agent absent).';
	}

	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		return 'Sol·licitud no vàlida (Referer absent).';
	}

	return true;
}
