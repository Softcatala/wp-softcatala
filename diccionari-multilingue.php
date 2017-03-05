<?php
/**
 * Template Name: Diccionari Multilingüe
 *
 * @package wp-softcatala
 */

$url_api = get_option( 'api_diccionari_multilingue' );

/* JS scripts */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-typeahead', get_template_directory_uri() . '/static/js/typeahead.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-multilingue', get_template_directory_uri() . '/static/js/multilingue.js', array('sc-js-typeahead'), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-multilingue', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'autocomplete_url' => $url_api . 'autocomplete/'
));

$paraula = urldecode( get_query_var('paraula') );
$lletra = get_query_var('lletra');
$content_title = 'Diccionari multilingüe';

$title = '';
$description = '';
$canonical = '';

$post = new TimberPost();
//Ads

$context_holder = array();
$context_holder['ads_container'] = true;

if( ! empty ( $paraula ) ) {

    $lang = 'ca';

    if ( get_query_var('llengua') ) {
        $lang = urldecode( get_query_var('llengua') );
        $context_holder['lang'] = $lang;
    }

    $multilingue = new SC_Multilingue();
    $r = $multilingue->get_paraula( $paraula, $lang );

    $canonical = $r_>canonical;
    $title = $r->title;
    $content_title = $r->content_title;
    $description = $r->description;
    $context_holder['cerca_result'] = $r->html;

} else if ( ! empty ( $lletra ) ) {
    if (strlen( $lletra ) == '1' ) {
        $url = $url_api.'index/' . $lletra;
        $api_response = json_decode( do_json_api_call($url) );
        if ( $api_response ) {
            $response['lletra'] = $lletra;
            $response['result'] = $api_response;

            $title = 'Diccionari multilingüe: paraules que comencen per ' . $lletra;
            $content_title =  'Diccionari multilingüe. Lletra «' . $lletra . '»';

            $canonical = '/diccionari-multilingue/lletra/' . $lletra . '/';

            $context_holder['cerca_result'] = Timber::fetch('ajax/multilingue-lletra.twig', array('response' => $response));
        } else {
            throw_error('500', 'Error connecting to API server');
            $context_holder['cerca_result'] = 'S\'ha produït un error en contactar amb el servidor. Proveu de nou.';
        }
    } else {
        throw_error('404', 'No Results For This Search');
        $context_holder['cerca_result'] = 'Esteu utilitzant la cerca per lletra. Heu cercat <strong>'. $context['lletra'] . '</strong>. La cerca només pot contenir una lletra';
    }
}

$context_filterer = new SC_ContextFilterer( $context_holder );

$context_overrides = array( 'title' => $title, 'description' => $description, 'canonical' => $canonical );

$context = $context_filterer->get_filtered_context( $context_overrides, false );

$context['post'] = $post;
$context['paraula'] = $paraula;
$context['lletra'] = $lletra;
$context['content_title'] = $content_title;

$context['links'] = $post->get_field( 'link' );
$context['credits'] = $post->get_field( 'credit' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['to_email'] = get_option('email_recursos');

Timber::render( array( 'diccionari-multilingue.twig' ), $context );
