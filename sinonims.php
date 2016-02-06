<?php
/**
 * Template Name: Sinònims Softcatala OpenThesaurus
 *
 * @package wp-softcatala
 */

/* JS scripts */
wp_enqueue_script( 'sc-js-sinonims', get_template_directory_uri() . '/static/js/sinonims.js', array(), '1.0.0', true );
wp_localize_script( 'sc-js-sinonims', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$url_sinonims_server = 'https://www.softcatala.org/sinonims/api/search?format=application/json&q=';

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
$context['paraula'] = urldecode( get_query_var('paraula') );
if( ! empty ( $context['paraula'] ) ) {
    $url = $url_sinonims_server . $context['paraula'];
    $sinonims_server = json_decode( file_get_contents( $url ) );
    $sinonims['paraula'] = $context['paraula'];
    $sinonims['response'] = $sinonims_server->synsets;
    $context['sinonims_result'] = Timber::fetch('ajax/sinonims-list.twig', array( 'sinonims' => $sinonims ) );
}
$context['content_title'] = 'Diccionari de sinònims';
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');
Timber::render( array( 'sinonims.twig' ), $context );
