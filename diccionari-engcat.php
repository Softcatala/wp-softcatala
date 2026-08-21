<?php
/**
 * Template Name: Diccionari anglès-català
 *
 * @package wp-softcatala
 */

/* JS scripts */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array( 'jquery' ), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-dict-eng-cat', get_template_directory_uri() . '/static/js/diccionari-engcat/diccionari-engcat.js', array(), WP_SOFTCATALA_VERSION, true );

wp_localize_script( 'sc-js-dict-eng-cat', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));


$timberPost = Timber::get_post();

//Ads
$context_holder['ads_container'] = true;

$content_title = 'Diccionari anglès-català';
$paraula = str_replace("'", '’', stripslashes( sanitize_text_field( urldecode( get_query_var('paraula') ) ) ) );
$lletra = sanitize_text_field( urldecode( get_query_var('lletra') ) );
$llengua = sanitize_text_field( urldecode( get_query_var('llengua') ) );

$canonical = '';
$description = '';

$diccionari = new SC_Diccionari_engcat();

if( ! empty ( $paraula ) && empty( $llengua ) ) {
    try {
        $r = $diccionari->get_paraula_with_language_detection( $paraula );
        
		if ( 200 === $r->status && ! empty( $r->detected_language ) && ! empty( $r->canonical_lemma ) ) {
            wp_redirect( home_url( '/diccionari-angles-catala/' . $r->detected_language . '/paraula/' . $r->canonical_lemma . '/' ), 301 );
            exit;
        }
    } catch ( Throwable $e ) {
        throw_service_error( $content_title, '', true );
    }
    // No redirect happened, assign a default language
    $llengua = 'eng';
} else if( ! empty ( $paraula ) && ! empty ( $llengua ) ) {
    try {
        $r = $diccionari->get_paraula( $paraula, $llengua );
        $canonical = $r->canonical;
	    $title = $r->title;
	    $content_title = $r->content_title;
	    $description = $r->description;
        $context_holder['engcat_resultat'] = $r->html;
 	    
    } catch ( Throwable $e ) {
        throw_service_error( $content_title, '', true );
    }
} else if ( ! empty ( $lletra ) && ! empty ( $llengua ) ) {
    
    $llengua_str = ($llengua == 'cat') ? 'català' : 'anglès';

    if (strlen( $lletra ) == '1' && ($llengua == 'cat' || $llengua == 'eng')) {   
        try {
            $r = $diccionari->get_lletra( $lletra, $llengua );

            $canonical = $r->canonical;
            $title = $r->title;
            $content_title = $r->content_title;
			$description = 'Índex de paraules en ' . $llengua_str . ' que comencen per «' . strtoupper( $lletra ) . '» al diccionari anglès-català.';
            $context_holder['engcat_resultat'] = $r->html;
            
        } catch ( Throwable $e ) {
            throw_service_error( $content_title, '', true );
        }
    } else {
		throw_error('404', 'No Results For This Search');
		$context_holder['cerca_result'] = 'Esteu utilitzant la cerca per lletra. Heu cercat <strong>'. $context['lletra'] . '</strong>. La cerca només pot contenir una lletra';
	}
}

$stats = false;
try {
    $stats = $diccionari->get_stats();
} catch ( Throwable $e ) {
    // Fail silently for stats
}

$context_overrides = array(
    'title'            => $title,
    'description'      => $description,
    'canonical'        => $canonical,
    'breadcrumb_title' => $content_title,
	'breadcrumb_parent_url' => get_permalink( $timberPost->ID ),
);

$context_filterer = new SC_ContextFilterer( $context_holder );

$context = $context_filterer->get_filtered_context( $context_overrides, false);

$context['post'] = $timberPost;
$context['paraula'] = $paraula;
$context['lletra'] = $lletra;
$context['llengua'] = $llengua;
$context['content_title'] = $content_title;
$context['credits'] = $timberPost->meta( 'credits' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');
$context['stats'] = $stats;

//Contact Form
$context['contact']['to_email'] = get_option('email_sinonims');

Timber::render( array( 'diccionari-engcat.twig' ), $context );
