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
/** DICCIONARI MULTILINGÜE */
add_action( 'wp_ajax_multilingue_search', 'sc_multilingue_search' );
add_action( 'wp_ajax_nopriv_multilingue_search', 'sc_multilingue_search' );

/**
 * Retrieves the results from the Multilingüe API server given a word + language
 *
 * @return json response
 */
function sc_multilingue_search()
{
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $_POST["action"])) {
        $result = 'S\'ha produït un error en contactar amb el servidor. Proveu de nou.';
    } else {
        $paraula = sanitize_text_field($_POST["paraula"]);
        $lang = sanitize_text_field($_POST["lang"]);

        $url_api = get_option('api_diccionari_multilingue');
        $url = $url_api . 'search/' . $paraula . '?lang=' . $lang;

        $api_call = do_json_api_call($url);

        if ( is_array( $api_call ) ) {
            throw_error('404', 'No Results For This Search');
            $result = 'Sembla que la paraula que esteu cercant no es troba al diccionari. Heu seleccionat la llengua correcta?';
        } else if ( $api_call && $api_call != 'error' ) {

            $api_response = json_decode($api_call);

            if (isset($api_response[0])) {
                $resultat_string = (count($api_response) > 1 ? 'resultats' : 'resultat');
                $result = 'Resultats de la cerca per: <strong>' . $paraula . '</strong> (' . count($api_response) . ' ' . $resultat_string . ') <hr class="clara"/>';
                foreach ($api_response as $single_entry) {
                    $response['paraula'] = $paraula;
                    $response['source'] = get_source_link($single_entry);

                    //Unset main source/other sources
                    $refs = (array)$single_entry->references;
                    unset($refs[$single_entry->source]);
                    $single_entry->references = $refs;
                    $response['result'] = $single_entry;

                    $result .= Timber::fetch('ajax/multilingue-paraula.twig', array('response' => $response));
                }
            } else {
                throw_error('404', 'No Results For This Search');
                $result = 'Sembla que la paraula que esteu cercant no es troba al diccionari. Heu seleccionat la llengua correcta?';
            }
        } else {
            throw_error('500', 'Error connecting to API server');
            $result = 'S\'ha produït un error en contactar amb el servidor. Proveu de nou.';
        }
    }

    echo json_encode($result);
    die();
}
/**
 * Function to make the request to synonims dictionary server
 *
 * @return json response
 */
function sc_subscribe_list() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] )) {
        $result['text'] = "S'ha produït un error. Proveu més tard.";
    } else {
        $nom = sanitize_text_field( $_POST["nom"] );
        $correu = sanitize_text_field( $_POST["correu"] );
        $llista = sanitize_text_field( $_POST["llista"] );
        $projecte = sanitize_text_field( $_POST["projecte"] );

        if( ! empty ( $llista )) {
            $password = get_option( 'llistes_access' );
            if ( ! empty ( $password )){
                $path = '/members/add?subscribe_or_invite=0&send_welcome_msg_to_this_batch=1&notification_to_list_owner=0&subscribees_upload='.$correu.'&adminpw='.$password;
                $list_admin_url = str_replace( 'listinfo', 'admin', $llista );
                $url = $list_admin_url . $path;
                $response_subscription = send_subscription_to_mailinglist($url);
                if ( $response_subscription['status'] ) {
                    $result['text'] = 'Gràcies per subscriure-vos a la llista. Ara heu de rebre un email de confirmació.';
                } else {
                    $result['text'] = "S'ha produït un error. " . $response_subscription['message'];
                }
            }
        } else {
            $to_email = 'web@softcatala.org';
            $subject = '[Projectes] Demanda de participació al projecte '. $projecte;
            $message = 'Un usuari ha demanat col·laborar al projecte '. $projecte;
            $message .= '<br/><br/>Atès que aquest projecte no té llista de correu, possiblement caldrà contactar l\'usuari';
            $message .= '<br/><br/><strong>Dades de l\'usuari</strong><br/><br/>Nom: '. $nom . '<br/>Email: '. $correu;

            //proceed with PHP email.
            $headers = 'From: '.$nom.' <'.$to_email. ">\r\n" .
                'Reply-To: '.$correu.'' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

            if ( wp_mail( $to_email, $subject, $message, $headers )) {
                $result['text'] = "Gràcies pel vostre interès. Ens posarem en contacte amb vosaltres aviat.";
            } else {
                $result['text'] = "S'ha produït un error. Proveu més tard.";
            }
        }
    }

    $response = json_encode( $result );
    die( $response );
}

/**
 * Function to make the request to synonims dictionary server
 *
 * @return json response
 */
function sc_find_sinonim() {
    $service_name = 'Diccionari de sinònims';
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] )) {
        $result = 'S\'ha produït un error en el servidor. Proveu més tard';
        throw_service_error( $service_name, 'Error wp_nonce' );
    } else {
        $paraula = sanitize_text_field( $_POST["paraula"] );
        $url_sinonims_server = get_option('api_diccionari_sinonims');
        $url = $url_sinonims_server . urlencode( $paraula );

        try {
            $sinonims_server = json_decode( do_json_api_call($url) );

            if( $sinonims_server != 'error' && count($sinonims_server->synsets) > 0) {
                $sinonims['paraula'] = $paraula;
                $sinonims['response'] = $sinonims_server->synsets;
                $result = Timber::fetch('ajax/sinonims-list.twig', array( 'sinonims' => $sinonims ) );
            } else if ( $sinonims_server == 'error' ) {
                throw_service_error( $service_name );
            } else {
                throw_error('404', 'No Results For This Search');
                $result = 'La paraula que esteu cercant no es troba al diccionari.';
            }
        } catch ( Exception $e ) {
            throw_service_error( $service_name );
        }
    }

    $response = json_encode( $result );
    die( $response );
}

/**
 * Function to send a contact form
 *
 * @return json response
 */
function sc_contact_form() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] )) {
        $output = json_encode(array('type'=>'error', 'text' => 'S\'ha produït un error en enviar el formulari.'));
    } else {
        $to_email       = sanitize_text_field( $_POST["to_email"] );
        $nom_from       = sanitize_text_field( $_POST["nom_from"] );
        $assumpte       = sanitize_text_field( $_POST["assumpte"] );

        //check if its an ajax request, exit if not
        if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            $output = json_encode(array( //create JSON data
                'type'=>'error',
                'text' => 'Sorry Request must be Ajax POST'
            ));
            die($output); //exit script outputting json data
        }

        //Sanitize input data using PHP filter_var().
        $nom      = sanitize_text_field( $_POST["nom"] );
        $correu     = sanitize_email( $_POST["correu"] );
        $tipus   = sanitize_text_field( $_POST["tipus"] );
        $comentari   = stripslashes(sanitize_text_field( ( $_POST["comentari"] ) ) );

        //email body
        $message_body = "Tipus: ".$tipus."\r\n\rComentari: ".$comentari."\r\n\rNom: ".$nom."\r\nCorreu electrònic: ".$correu;

        //proceed with PHP email.
        $headers = 'From: '.$nom_from.' <'.$to_email. ">\r\n" .
            'Reply-To: '.$correu.'' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        $send_mail = wp_mail($to_email, $assumpte, $message_body, $headers);

        if(!$send_mail) {
            //If mail couldn't be sent output error. Check your PHP email configuration (if it ever happens)
            $output = json_encode(array('type'=>'error', 'text' => 'S\'ha produït un error en enviar el formulari.'));
        } else {
            $output = json_encode(array('type'=>'message', 'text' => $nom .', et donem les gràcies per ajudar-nos a millorar el nostre lloc web.'));
        }
    }

    echo $output;
    die();
}

/**
 * Function to add a download related to a program
 *
 * @return json response
 */
function sc_add_new_baixada() {
    $return = array();
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] )) {
        $return['status'] = 0;
    } else {
        $baixades = json_decode(stripslashes($_POST["baixades"]));
        $programa_id = sanitize_text_field( $_POST["programa_id"] );
        $taxonomy = 'sistema-operatiu-programa';

        //Related downloads
        $version_info = array();
        $terms = array();
        foreach ( $baixades as $key => $baixada ) {
            $version_info[$key]['download_url'] = $baixada->url;
            $version_info[$key]['download_version'] = $baixada->versio;
            $version_info[$key]['download_size'] = '';
            $version_info[$key]['arquitectura'] = $baixada->arquitectura;
            $version_info[$key]['download_os'] = map_so($baixada->sistema_operatiu);
            $terms[] = $baixada->sistema_operatiu;
        }

        $field_key = acf_get_field_key( 'baixada', $programa_id );
        update_field( $field_key, $version_info, $programa_id );
        $return['status'] = 1;
    }

    wp_set_post_terms( $programa_id, $terms, $taxonomy );

    $response = json_encode( $return );
    die( $response );
}

/**
 * Function to add a new draft program into database
 *
 * @return json response
 */
function sc_add_new_program() {
    $return = array();
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] )) {
        $return['status'] = 0;
    } else {
        $nom = sanitize_text_field( $_POST["nom"] );
        $email_usuari = sanitize_email( $_POST["email_usuari"] );
        $comentari_usuari = sanitize_text_field( $_POST["comentari_usuari"] );
        $descripcio = sanitize_text_field( $_POST["descripcio"] );
        $autor_programa = sanitize_text_field( $_POST["autor_programa"] );
        $lloc_web_programa = sanitize_text_field( $_POST["lloc_web_programa"] );
        $llicencia = sanitize_text_field( $_POST["llicencia"] );
        $categoria_programa = sanitize_text_field( $_POST["categoria_programa"] );
        $slug = sanitize_title_with_dashes( $nom );

        $terms = array(
            'categoria-programa' => array($categoria_programa),
            'llicencia' => array($llicencia)
        );

        $metadata = array(
            'autor_programa' => $autor_programa,
            'lloc_web_programa' => $lloc_web_programa
        );

        $return = sc_add_draft_content('programa', $nom, $descripcio, $slug, $terms, $metadata);

        if( $return['status'] == 1 ) {
            //Logo and screenshot file upload
            $logo_attach_id = sc_upload_file( 'logo', $return['post_id'] );
            $screenshot_attach_id = sc_upload_file( 'captura', $return['post_id'] );
            $metadata = array(
                'logotip_programa' => wp_get_attachment_url( $logo_attach_id ),
                'imatge_destacada_1' => wp_get_attachment_url( $screenshot_attach_id )
            );
            sc_update_metadata ( $return['post_id'], $metadata );

            $to_email = get_option( 'email_rebost ' );
            $nom_from = "Programes i aplicacions de Softcatalà";
            $assumpte = "[Programes] Programa enviat per formulari";

            $fields = array(
                "Nom del programa" => $nom,
                "Descripció" => $descripcio,
                "Comentari de l'usuari" => $comentari_usuari,
                "Email de l'usuari" => $email_usuari,
                "URL Dashboard" => admin_url("post.php?post=" . $return['post_id'] . "&action=edit")
            );
            sendEmailForm($to_email, $nom_from, $assumpte, $fields);
        }
    }

    $response = json_encode( $return );
    die( $response );

}

/**
 * Function to look up a program with a title similar to the title from the search on the add program form
 *
 * @return json response
 */
function sc_search_program() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] )) {
        $result['text'] = "S'ha produït un error en cercar el programa. Podeu cotinuar igualment.";
    } else {
        check_is_ajax_call();

        $nom_programa = sanitize_text_field( $_POST["nom_programa"] );

        $result = array();
        if( ! empty ( $nom_programa ) ) {
            $query['s'] = $nom_programa;
            $args = get_post_query_args( 'programa', SearchQueryType::Programa, $query );
            $result_full = query_posts( $args );
        }

        $programs = array_map( 'generate_post_url_link', $result_full );

        if ( count( $programs ) > 0 ) {
            $result['programs'] = Timber::fetch('ajax/programs-list.twig', array( 'programs' => $programs ) );
            $result['text'] = "El programa que proposeu és algun dels que es mostren a continuació?";
        } else {
            $result['text'] = "El programa no està a la nostra base de dades. Podeu continuar!";
        }
    }

    $response = json_encode( $result );
    die( $response );
}

/**
 * This function increments the vote count for a 'programa' post type and calculates
 * the new rate
 *
 * @return json response
 */
function sc_send_vote() {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] )) {
        $return['text'] = "No s'ha pogut enviar el vot. Proveu més tard.";
    } else {
        check_is_ajax_call();

        $post_id = intval(sanitize_text_field( $_POST["post_id"] ));
        $rate = sanitize_text_field( $_POST["rate"] );
        $single = true;

        $current_rating = get_post_meta( $post_id, 'wpcf-valoracio', $single );
        $votes = get_post_meta( $post_id, 'wpcf-vots', $single );

        $new_votes = $votes + 1;
        $new_rate = $current_rating * ( $votes/ $new_votes ) + $rate * ( 1/$new_votes );

        $metadata = array(
            'valoracio'   => number_format((float)$new_rate, 2, '.', ''),
            'vots' => $new_votes
        );

        $result = sc_update_metadata( $post_id, $metadata );

        if ( ! $result ) {
            $return['status'] = 0;
            $return['text'] = "No s'ha pogut enviar el vot. Proveu més tard.";
        } else {
            $return['status'] = 1;
            $return['cookie_id'] = sanitize_text_field( $_POST["cookie_id"] );
            $return['text'] = "Gràcies per enviar-nos la vostra valoració!";
        }
    }

    echo json_encode( $return );
    die();
}

/**
 * Creates a new post of the type 'aparell' using the data sent from the form ($_POST)
 *
 * @return json response
 */
function sc_send_aparell() {
    $return = array();
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $_POST["action"] )) {
        $return['status'] = 0;
    } else {
        check_is_ajax_call();

        $nom = sanitize_text_field( $_POST["nom"] );
        $tipus_aparell = sanitize_text_field( $_POST["tipus_aparell"] );
        $fabricant   = sanitize_text_field( $_POST["fabricant"] );
        $sistema_operatiu = sanitize_text_field( $_POST["sistema_operatiu"] );
        $versio = sanitize_text_field( $_POST["versio"] );
        $traduccio_catala = sanitize_text_field( $_POST["traduccio_catala"] );
        $correccio_catala = sanitize_text_field( $_POST["correccio_catala"] );
        $slug = sanitize_title_with_dashes( $nom );

        // comentari no s'utilitza
        $comentari = stripslashes( sanitize_text_field( $_POST["comentari"] ) );

        $terms = array(
            'tipus_aparell' => array($tipus_aparell),
            'so_aparell' => array($sistema_operatiu)
        );

        $metadata = array(
            'versio' => $versio,
            'fabricant' => $fabricant,
            'conf_cat' => $traduccio_catala,
            'correccio_cat' => $correccio_catala );

        $return = sc_add_draft_content('aparell', $nom, '', $slug, $terms, $metadata);

        if( $return['status'] == 1 ) {
            $to_email       = "rebost@llistes.softcatala.org";
            $nom_from       = "Aparells de Softcatalà";
            $assumpte       = "[Aparells] Aparell enviat per formulari";

            $fields = array (
                "Nom de l'aparell" => $nom,
                "Comentari" => $comentari,
                "URL Dashboard" => admin_url( "post.php?post=".$return['post_id']."&action=edit" )
            );
            sendEmailForm( $to_email, $nom_from, $assumpte, $fields );
        }
    }

    $response = json_encode( $return );
    die( $response );
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
 * @return array|mixed|void
 */
function sc_add_draft_content ( $type, $nom, $descripcio, $slug, $allTerms, $metadata ) {
    $return = array();
    if( isset( $metadata['post_id'] ) ){
        $parent_id = $metadata['post_id'];
        unset($metadata['post_id']);
        $post_status = 'publish';
    } else {
        $post_status = 'pending';
    }

    //Generate array data
    $post_data = array (
        'post_type'         =>  $type,
        'post_status'		=>	$post_status,
        'comment_status'	=>	'open',
        'ping_status'		=>	'closed',
        'post_author'		=>	get_current_user_id(),
        'post_name'		    =>	$slug,
        'post_title'		=>	$nom,
        'post_content'      =>  $descripcio,
        'post_date'         => date('Y-m-d H:i:s')
    );

    $post_id = wp_insert_post( $post_data );
    if( $post_id ) {

        foreach( $allTerms as $taxonomy => $terms ) {
            wp_set_post_terms( $post_id, $terms, $taxonomy );
        }

        sc_update_metadata( $post_id, $metadata );

        if ( $type == 'aparell' ) {
            $featured_image_attach_id = sc_upload_file( 'file', $post_id );
            if($featured_image_attach_id) {
                $return = sc_set_featured_image( $post_id, $featured_image_attach_id );
            } else {
                $return['status'] = 1;
            }
        } else {
            $return['status'] = 1;
        }

    } else {
        $return['status'] = 0;
        $return['text'] = "S'ha produït un error en enviar les dades. Proveu de nou.";
    }

    if( $return['status'] == 1 ) {
        $return['post_id'] = $post_id;
        $return['text'] = 'Gràcies per enviar aquesta informació. La publicarem el més aviat possible.';
    }

    return $return;
}

/**
 * This funcions uploads a file to the wordpress media library
 *
 * @param $value
 * @param $post_id
 * @return bool|int
 */
function sc_upload_file( $value, $post_id ) {
    if( isset( $_FILES[$value] ) ) {
        $tmpfile = $_FILES[$value];

        $upload_overrides = array('test_form' => false);

        $uploaded = wp_handle_upload( $tmpfile, $upload_overrides );

        if ( $uploaded && ! isset( $uploaded['error']) ) {

            $wp_filetype = wp_check_filetype(basename($uploaded['file']), null);

            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace('/.[^.]+$/', '', basename($uploaded['file'])),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $uploaded['file'], $post_id);

            $attach_data = wp_generate_attachment_metadata($attach_id, $uploaded['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

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
 * @return mixed
 */
function sc_set_featured_image( $post_id, $attach_id ) {
    if( $attach_id ) {
        set_post_thumbnail( $post_id, $attach_id );
        $return['status'] = 1;
    } else {
        $return['status'] = 0;
        $return['text'] = "S'ha produït un error en pujar la imatge. Proveu de nou.";
    }

    return $return;
}

/** General **/
function check_is_ajax_call() {
    //check if its an ajax request, exit if not
    if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        $output = json_encode(array( //create JSON data
            'type'=>'error',
            'text' => 'Sorry Request must be Ajax POST'
        ));
        die($output); //exit script outputting json data
    }
}

/**
 * This function updates an array of given post metadata
 *
 * @param int $post_id
 * @param array $metadata
 * @return boolean
 */
function sc_update_metadata( $post_id, $metadata ) {
    $result = false;
    if( $post_id ) {
        global $wpcf;

        foreach ($metadata as $meta_key => $meta_value) {
            $wpcf->field->set( $post_id, $meta_key );
            $wpcf->field->save( $meta_value );
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
    $acf_fields = $wpdb->get_results( $wpdb->prepare( "SELECT ID,post_parent,post_name FROM $wpdb->posts WHERE post_excerpt=%s AND post_type=%s" , $field_name , 'acf-field' ) );
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
    $field_groups = acf_get_field_groups( array(
        'post_id' => $post_id,
    ) );
    foreach ( $field_groups as $field_group )
        $field_groups_ids[] = $field_group['ID'];

    // Check if field is part of one of the field groups
    // Return the first one.
    foreach ( $acf_fields as $acf_field ) {
        if ( in_array($acf_field->post_parent,$field_groups_ids) )
            return $acf_fields[0]->post_name;
    }
    return false;
}

/**
 * Maps the category so ID with the program so value
 */
function map_so($so_id) {
    switch ($so_id) {
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
