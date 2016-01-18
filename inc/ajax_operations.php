<?php

/** APARELLS **/
add_action( 'wp_ajax_send_aparell', 'sc_send_aparell' );
add_action( 'wp_ajax_nopriv_send_aparell', 'sc_send_aparell' );

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

    $success = sc_add_draft_content('aparell', $nom, $slug, $terms, $metadata);

    echo $success;
    wp_die();
}

function sc_add_draft_content ( $type, $title, $slug, $allTerms, $metadata) {

    //Generate array data
    $post_data = array (
        'post_type'         =>  'aparell',
        'post_status'		=>	'pending',
        'comment_status'	=>	'open',
        'ping_status'		=>	'closed',
        'post_author'		=>	get_current_user_id(),
        'post_name'		    =>	$slug,
        'post_title'		=>	$nom,
        'post_date'         => date('Y-m-d H:i:s')
    );

    $post_id = wp_insert_post($post_data);
    if( $post_id ) {
        global $wpcf;

        foreach($allTerms as $taxonomy => $terms) {
            wp_set_post_terms($post_id, $terms, $taxonomy);
        }

        foreach($metadata as $meta_key => $meta_value ) {
            $wpcf->field->set( $post_id, $meta_key );
            $wpcf->field->save( $meta_value );
        }

        sc_set_featured_image($post_id);

        $success = true;
    } else {
        $success = false;
    }

    return $success;
}

function sc_set_featured_image($post_id) {
    $tmpfile = $_FILES['file'];

    $upload_overrides = array( 'test_form' => false );

    $uploaded = wp_handle_upload( $tmpfile, $upload_overrides );

    if ( $uploaded && !isset( $uploaded['error'] ) ) {

            $wp_filetype = wp_check_filetype( basename( $uploaded['file'] ), null );

            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace('/.[^.]+$/', '', basename( $uploaded['file'] ) ),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment( $attachment, $uploaded['file'], $post_id );

            $attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded['file'] );
            wp_update_attachment_metadata( $attach_id,  $attach_data );

            set_post_thumbnail( $post_id, $attach_id );

            return true;
    } else {
        return false;
    }
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