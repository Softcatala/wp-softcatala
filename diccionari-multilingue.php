<?php
/**
 * Template Name: Diccionari Multilingüe
 *
 * @package wp-softcatala
 */

/* JS scripts */
wp_enqueue_script( 'sc-js-sinonims', get_template_directory_uri() . '/static/js/multilingue.js', array(), '1.0.0', true );
wp_enqueue_script( 'sc-js-typeahead', get_template_directory_uri() . '/static/js/typeahead.js', array(), '1.0.0', true );
wp_localize_script( 'sc-js-sinonims', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$url_api = 'https://www.softcatala.org/diccionari-multilingue/api/';

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
$context['paraula'] = urldecode( get_query_var('paraula') );
$context['lletra'] = urldecode( get_query_var('lletra') );

if( ! empty ( $context['paraula'] ) ) {
    $url = $url_api.'search/' . $context['paraula'];
    if ( get_query_var('llengua') ) {
        $lang = urldecode( get_query_var('llengua') );
        $url = $url . '?lang='. $lang;
        $context['lang'] = $lang;
    }
    $api_response = json_decode( file_get_contents( $url ) );

    if ( isset( $api_response[0] ) ) {
        $response['result'] = $api_response[0];
    } else {
        $response['message'] = 'Sembla que la paraula que esteu cercant no es troba al diccionari. Heu seleccionat la llengua correcta?';
    }

    $response['paraula'] = $context['paraula'];

    $context['cerca_result'] = Timber::fetch('ajax/multilingue-paraula.twig', array( 'response' => $response ) );
} else if ( ! empty ( $context['lletra'] ) ) {
    $url = $url_api.'index/' . $context['lletra'];
    $api_response = json_decode( file_get_contents( $url ) );
    $response['lletra'] = $context['lletra'];
    $response['result'] = $api_response;

    $context['cerca_result'] = Timber::fetch('ajax/multilingue-lletra.twig', array( 'response' => $response ) );
}
$context['content_title'] = 'Diccionari multilingüe';
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');
Timber::render( array( 'diccionari-multilingue.twig' ), $context );
