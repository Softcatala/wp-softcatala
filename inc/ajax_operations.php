<?php

/** APARELLS **/
add_action( 'wp_ajax_send_aparell', 'sc_send_aparell' );
add_action( 'wp_ajax_nopriv_send_aparell', 'sc_send_aparell' );
/** PROGRAMES **/
add_action( 'wp_ajax_send_vote', 'sc_send_vote' );
add_action( 'wp_ajax_nopriv_send_vote', 'sc_send_vote' );
add_action( 'wp_ajax_increment_download', 'sc_increment_download_count' );
add_action( 'wp_ajax_nopriv_increment_download', 'sc_increment_download_count' );

/**
 * This function increments the download count for a 'programa' and a 'baixada' post type
 *
 * @return json response
 */
function sc_increment_download_count() {
    check_is_ajax_call();

    $post_id = intval(sanitize_text_field( $_POST["post_id"] ));
    $baixada_id = intval(sanitize_text_field( $_POST["baixada_id"] ));
    $single = true;

    $current_downloads_programa = get_post_meta( $post_id, 'wpcf-total_baixades_programa', $single );
    $current_downloads_baixada = get_post_meta( $baixada_id, 'wpcf-total_baixades_baixada', $single );

    $metadata_programa = array(
        'total_baixades_programa' => $current_downloads_programa + 1
    );

    $metadata_baixada = array(
        'total_baixades_baixada' => $current_downloads_baixada + 1
    );

    sc_update_metadata( $post_id, $metadata_programa );
    sc_update_metadata( $baixada_id, $metadata_baixada );

    die(0);
}

/**
 * This function increments the vote count for a 'programa' post type and calculates
 * the new rate
 *
 * @return json response
 */
function sc_send_vote() {
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
        $return['text'] = "Gràcies per enviar-nos la vostra valoració!";
    }

    $response = json_encode( $return );
    die( $response );
}

/**
 * Creates a new post of the type 'aparell' using the data sent from the form ($_POST)
 *
 * @return json response
 */
function sc_send_aparell() {
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
        'sistema_operatiu_aparell' => array($sistema_operatiu)
    );

    $metadata = array(
        'versio' => $versio,
        'fabricant' => $fabricant,
        'conf_cat' => $traduccio_catala,
        'correccio_cat' => $correccio_catala );

    $return = sc_add_draft_content('aparell', $nom, $slug, $terms, $metadata);

    if( $return['status'] == 1 ) {
        $to_email       = "web@softcatala.org";
        $nom_from       = "Aparells de Softcatalà";
        $assumpte       = "[Aparells] Aparell enviat per formulari";

        $fields = array (
            "Nom de l'aparell" => $nom,
            "Comentari" => $comentari,
            "URL Dashboard" => admin_url( "post.php?post=".$return['post_id']."&action=edit" )
        );
        sendEmailForm( $to_email, $nom_from, $assumpte, $fields );
    }

    $response = json_encode( $return );
    die( $response );
}

function sc_add_draft_content ( $type, $nom, $slug, $allTerms, $metadata ) {

    $return = array();

    //Generate array data
    $post_data = array (
        'post_type'         =>  $type,
        'post_status'		=>	'pending',
        'comment_status'	=>	'open',
        'ping_status'		=>	'closed',
        'post_author'		=>	get_current_user_id(),
        'post_name'		    =>	$slug,
        'post_title'		=>	$nom,
        'post_date'         => date('Y-m-d H:i:s')
    );

    $post_id = wp_insert_post( $post_data );
    if( $post_id ) {
        foreach( $allTerms as $taxonomy => $terms ) {
            wp_set_post_terms( $post_id, $terms, $taxonomy );
        }

        sc_update_metadata( $post_id, $metadata );

        $return = sc_set_featured_image($post_id);
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

function sc_set_featured_image($post_id) {
    if( isset( $_FILES['file'] ) ) {
        $tmpfile = $_FILES['file'];

        $upload_overrides = array('test_form' => false);

        $uploaded = wp_handle_upload($tmpfile, $upload_overrides);

        if ($uploaded && !isset($uploaded['error'])) {

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

            set_post_thumbnail($post_id, $attach_id);

            $return['status'] = 1;
        } else {
            $return['status'] = 0;
            $return['text'] = "S'ha produït un error en pujar la imatge. Proveu de nou.";
        }
    } else {
        $return['status'] = 1;
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