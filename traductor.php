<?php
/**
 * Template Name: Traductor Softcatala
 *
 * @package wp-softcatala
 */

if($_POST) {
    sendContactForm();
} else {
    $context = Timber::get_context();
    $post = new TimberPost();
    $context['post'] = $post;
    $context['sidebar_top'] = Timber::get_widgets('sidebar_top');
    $context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
    Timber::render( array( 'traductor.twig' ), $context );
}
    
/**
 * If a POST is received, that means that someone is contacting us.
 *
 * @param $_POST
 * @return string
 */
function sendContactForm() {
    $to_email       = "traductor@softcatala.org";
    $assumpte       = "[Traductor] Contacte des del formulari";
    
    //check if its an ajax request, exit if not
    if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        $output = json_encode(array( //create JSON data
            'type'=>'error',
            'text' => 'Sorry Request must be Ajax POST'
        ));
        die($output); //exit script outputting json data
    }
    
    //Sanitize input data using PHP filter_var().
    $nom      = filter_var($_POST["nom"], FILTER_SANITIZE_STRING);
    $correu     = filter_var($_POST["correu"], FILTER_SANITIZE_EMAIL);
    $tipus   = filter_var($_POST["tipus"], FILTER_SANITIZE_STRING);
    $comentari   = filter_var($_POST["comentari"], FILTER_SANITIZE_STRING);
    
    //email body
    $message_body = "Tipus: ".$tipus."\r\n\r\Comentari: ".$comentari."\r\n\r\Nom: ".$nom."\r\nCorreu electrònic: ".$correu;
    
    //proceed with PHP email.
    $headers = 'From: '.$nom.'' . "\r\n" .
    'Reply-To: '.$correu.'' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
    
    $send_mail = mail($to_email, $assumpte, $message_body, $headers);
    
    if(!$send_mail) {
        //If mail couldn't be sent output error. Check your PHP email configuration (if it ever happens)
        $output = json_encode(array('type'=>'error', 'text' => 'S\'ha produït un error en enviar el formulari.'));
        die($output);
    } else {
        $output = json_encode(array('type'=>'message', 'text' => $nom .', et donem les gràcies per ajudar-nos a millorar el traductor.'));
        die($output);
    }
}