<?php
/**
 * Template Name: Conjugador
 *
 * @package wp-softcatala
 */

$url_api = get_option( 'api_conjugador' );

/* JS scripts */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-typeahead', get_template_directory_uri() . '/static/js/typeahead.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-conjugador', get_template_directory_uri() . '/static/js/conjugador/conjugador.js', array('sc-js-typeahead'), WP_SOFTCATALA_VERSION, true );

wp_enqueue_style( 'sc-css-conjugador', get_template_directory_uri() . '/static/css/conjugador.css', array('sc-css-main'),WP_SOFTCATALA_VERSION );


wp_localize_script( 'sc-js-conjugador', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'autocomplete_url' => $url_api . 'autocomplete/'
));


$verb = urldecode( get_query_var('verb') );
$lletra = get_query_var('lletra');
$content_title = 'Conjugador de verbs';

$title = '';
$description = '';
$canonical = '';

$post = new TimberPost();
//Ads

$context_holder = array();
$context_holder['ads_container'] = true;


if( ! empty ( $verb ) ) {
 
    $conjugador = new SC_Conjugador();
    
    $r = $conjugador->get_verb( $verb );

    $canonical = $r->canonical;
    $title = $r->title;
    $content_title = $r->content_title;
    $description = $r->description;
    $context_holder['cerca_result'] = $r->html;

} else if ( ! empty ( $lletra ) ) {
  
        $conjugador = new SC_Conjugador();
    
        $r = $conjugador->get_lletra( $lletra );

        $canonical = $r->canonical;
        $title = $r->title;
        $content_title = $r->content_title;
        $description = $r->description;
        $context_holder['cerca_result'] = $r->html;
   
}

$context_filterer = new SC_ContextFilterer( $context_holder );

$context_overrides = array( 'title' => $title, 'description' => $description, 'canonical' => $canonical );

$context = $context_filterer->get_filtered_context( $context_overrides, false );

$context['post'] = $post;
$context['verb'] = $verb;
$context['lletra'] = $lletra;
$context['description'] = $description;
$context['content_title'] = $content_title;


$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['to_email'] = get_option('email_recursos');

Timber::render( array( 'conjugador.twig' ), $context );
