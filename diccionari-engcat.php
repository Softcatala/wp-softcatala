<?php
/**
 * Template Name: Diccionari Anglès-Català
 *
 * @package wp-softcatala
 */

/* JS scripts */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-dict-eng-cat', get_template_directory_uri() . '/static/js/diccionari-engcat/diccionari-engcat.js', array(), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-dict-eng-cat', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));


$timberPost = new TimberPost();

//Ads
$context_holder['ads_container'] = true;

$content_title = 'Diccionari Anglès-Català';
$paraula = str_replace("'", '’', stripslashes( sanitize_text_field( urldecode( get_query_var('paraula') ) ) ) );

$canonical = '';
$prefix_description = '';

if( ! empty ( $paraula ) ) {
    try {
        $diccionari = new SC_Diccionari_engcat();
               
        $r = $diccionari->get_paraula($paraula);
  
        $canonical = $r->canonical;
	    $title = $r->title;
	    $content_title = $r->content_title;
	    $prefix_description = ' «' . $paraula . '» en català. ';
	    $context_holder['engcat_resultat'] = $r->html;
 	    
    } catch ( Exception $e ) {
        throw_service_error( $content_title, '', true );
    }
} 

$context_overrides = array( 'title' => $title, 'prefix_description' => $prefix_description, 'canonical' => $canonical );

$context_filterer = new SC_ContextFilterer( $context_holder );

$context = $context_filterer->get_filtered_context( $context_overrides, false);

$context['post'] = $timberPost;
//$context['paraula'] = $paraula;
$context['content_title'] = $content_title;
$context['credits'] = $timberPost->get_field( 'credits' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['to_email'] = get_option('email_sinonims');

Timber::render( array( 'diccionari-engcat.twig' ), $context );
