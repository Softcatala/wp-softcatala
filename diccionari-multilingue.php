<?php
/**
 * Template Name: Diccionari Multilingüe
 *
 * @package wp-softcatala
 */

/* JS scripts */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), '1.0.0', true );
wp_enqueue_script( 'sc-js-typeahead', get_template_directory_uri() . '/static/js/typeahead.js', array('sc-js-main'), '1.0.0', true );
wp_enqueue_script( 'sc-js-multilingue', get_template_directory_uri() . '/static/js/multilingue.js', array('sc-js-typeahead'), '1.0.0', true );
wp_localize_script( 'sc-js-multilingue', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$url_api = get_option( 'api_diccionari_multilingue' );

$paraula = urldecode( get_query_var('paraula') );
$lletra = get_query_var('lletra');
$content_title = 'Diccionari multilingüe';

$title = '';
$description = '';
$canonical = '';

$post = new TimberPost();
//Ads
$context_holder['ads_container'] = generate_ads_html( array( '13', '17' ));

$context_holder = array();

if( ! empty ( $paraula ) ) {
    $url = $url_api.'search/' . $paraula;
    if ( get_query_var('llengua') ) {
        $lang = urldecode( get_query_var('llengua') );
        $url = $url . '?lang='. $lang;
        $context_holder['lang'] = $lang;
    }

    $api_call = do_json_api_call($url);
    $api_response = json_decode($api_call);

    if ( $api_call ) {
        if ( isset( $api_response[0] ) ) {
            $resultat_string = ( count($api_response) > 1 ? 'resultats' : 'resultat');
            $result = 'Resultats de la cerca per: <strong>'.$paraula.'</strong> ('.count($api_response).' '.$resultat_string.') <hr class="clara"/>';

			$title = 'Diccionari multilingüe: ' . $paraula . '. Definició i traducció al català, anglès, alemany, francès, italià i espanyol | Softcatalà';
			$content_title =  'Diccionari multilingüe: «' . $paraula . '»';

			if( isset( $llengua ) ) {
				$canonical = '/diccionari-multilingue/paraula/' . $api_response[0]->word_ca . '/';
			} else {
				$canonical = '/diccionari-multilingue/paraula/' . $paraula . '/';
			}

			if ( property_exists( $api_response[0], 'definition_ca' ) ) {
				$description = 'Definició de «' . $paraula .'»: ' .  $api_response[0]->definition_ca . '. Traduccions al català, anglès, alemany, francès, italià i espanyol';
			} else {
				$description = 'Definició de la paraula «' . $paraula .'» i traduccions al català, anglès, alemany, francès, italià i espanyol';
			}

            foreach ( $api_response as $single_entry ) {
                $response['paraula'] = $paraula;
                $response['source'] = get_source_link($single_entry);

                //Unset main source/other sources
                $refs = (array) $single_entry->references;
                unset($refs[$single_entry->source]);
                $single_entry->references = $refs;

                $response['result'] = $single_entry;
                $result .= Timber::fetch('ajax/multilingue-paraula.twig', array( 'response' => $response ) );
            }
        } else {
            throw_error('404', 'No Results For This Search');
            $result = 'Sembla que la paraula que esteu cercant no es troba al diccionari. Heu seleccionat la llengua correcta?';
        }
        $context_holder['cerca_result'] = $result;
    } else {
        throw_error('500', 'Error connecting to API server');
        $context_holder['cerca_result'] = 'S\'ha produït un error en contactar amb el servidor. Proveu de nou.';
    }

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
