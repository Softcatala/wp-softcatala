<?php
/**
 * Template Name: Sinònims Softcatala OpenThesaurus
 *
 * @package wp-softcatala
 */

/* JS scripts */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-sinonims', get_template_directory_uri() . '/static/js/sinonims.js', array(), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-sinonims', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$url_sinonims_server = get_option('api_diccionari_sinonims');

$post = new TimberPost();
//Ads
$context_holder['ads_container'] = generate_ads_html( array( '13', '17' ));
$paraula = sanitize_text_field( urldecode( get_query_var('paraula') ) );

$content_title = 'Diccionari de sinònims';

if( ! empty ( $paraula ) ) {
    $url = $url_sinonims_server . urlencode( $paraula );
    try {
        $sinonims_server = json_decode( do_json_api_call($url) );

        if( $sinonims_server != 'error' && count($sinonims_server->synsets) > 0) {
            $sinonims['paraula'] = $paraula;
            $sinonims['response'] = $sinonims_server->synsets;

            $content_title = 'Diccionari de sinònims: «' . $paraula . '»';
            $title = 'Diccionari de sinònims en català: «' . $paraula . '»';
            $prefix_description = 'Sinònims de «' . $paraula . '» en català.';
            $canonical = get_current_url();

            $context_holder['sinonims_result'] = Timber::fetch('ajax/sinonims-list.twig', array( 'sinonims' => $sinonims ) );
        } else if ( $sinonims_server == 'error' ) {
            throw_service_error( $content_title );
        } else {
            throw_error('404', 'No Results For This Search');
            $context_holder['sinonims_result'] = 'La paraula que esteu cercant no es troba al diccionari.';
        }
    } catch ( Exception $e ) {
        throw_service_error( $content_title );
    }
}

$context_overrides = array( 'title' => $title, 'prefix_description' => $prefix_description, 'canonical' => $canonical );

$context_filterer = new SC_ContextFilterer( $context_holder );

$context = $context_filterer->get_filtered_context( $context_overrides, false);

$context['post'] = $post;
$context['paraula'] = $paraula;
$context['content_title'] = $content_title;
$context['links'] = $post->get_field( 'link' );
$context['credits'] = $post->get_field( 'credit' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['to_email'] = get_option('email_recursos');

Timber::render( array( 'sinonims.twig' ), $context );
