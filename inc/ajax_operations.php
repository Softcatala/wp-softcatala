<?php

/** APARELLS **/
add_action( 'wp_ajax_send_aparell', 'sc_send_aparell' );
add_action( 'wp_ajax_nopriv_send_aparell', 'sc_send_aparell' );

function sc_send_aparell() {
    check_is_ajax_call();

    $nom = sanitize_text_field( $_POST["data"]["nom"] );
    $tipus_aparell = sanitize_text_field( $_POST["data"]["tipus_aparell"] );
    $fabricant   = sanitize_text_field( $_POST["data"]["fabricant"] );
    $sistema_operatiu = sanitize_text_field( $_POST["data"]["sistema_operatiu"] );
    $versio = sanitize_text_field( $_POST["data"]["versio"] );
    $traduccio_catala = sanitize_text_field( $_POST["data"]["traduccio_catala"] );
    $correccio_catala = sanitize_text_field( $_POST["data"]["correccio_catala"] );
    $comentari = stripslashes( sanitize_text_field( $_POST["data"]["comentari"] ) );

    //Generate array data
    $post_data = array (
        'post_type'         =>  'aparell',
        'post_status'		=>	'pending',
        'comment_status'	=>	'open',
        'ping_status'		=>	'closed',
        'post_author'		=>	get_current_user_id(),
        'post_name'		    =>	sanitize_title_with_dashes( $nom ),
        'post_title'		=>	$nom,
        'post_date'         => date('Y-m-d H:i:s')
    );

    $post_id = wp_insert_post($post_data);
    if( $post_id ) {
        global $wpcf;

        //Set categories
        wp_set_post_terms($post_id, array($tipus_aparell), 'tipus_aparell');
        wp_set_post_terms($post_id, array($sistema_operatiu), 'sistema_operatiu_aparell');

        //Set wpcf fields
        $wpcf_values = array( 'versio' => $versio, 'fabricant' => $fabricant, 'conf_cat' => $traduccio_catala, 'correccio_cat' => $correccio_catala );
        foreach ( $wpcf_values as $k => $value ) {
            $wpcf->field->set( $post_id, $k );
            $wpcf->field->save( $value );
        }

        $success = true;
    } else {
        $success = false;
    }

    echo $success;
    wp_die();
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