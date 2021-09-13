<?php
/**
 * Template Name: Sinònims Softcatala OpenThesaurus
 *
 * @package wp-softcatala
 */

/* JS scripts */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-typeahead', get_template_directory_uri() . '/static/js/typeahead.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-sinonims', get_template_directory_uri() . '/static/js/sinonims.js', array(), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-sinonims', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$timberPost = new TimberPost();

//Ads
$context_holder['ads_container'] = true;
$paraula = str_replace("'", '’', stripslashes( sanitize_text_field( urldecode( get_query_var('paraula') ) ) ) );
$lletra = sanitize_text_field( urldecode( get_query_var('lletra') ) );

$content_title = 'Diccionari de sinònims';

$canonical = '';
$prefix_description = '';

if( ! empty ( $paraula ) ) {
    try {
	    $sinonims = new SC_Sinonims();

	    $r = $sinonims->get_paraula($paraula);

	    $canonical = $r->canonical;
	    $title = $r->title;
	    $content_title = $r->content_title;
	    $prefix_description = 'Sinònims de «' . $r->canonical_lemma . '» en català.';
	    $context_holder['sinonims_result'] = $r->html;
    } catch ( Exception $e ) {
        throw_service_error( $content_title, '', true );
    }
} else if ( ! empty ( $lletra ) ) {
	if (strlen( $lletra ) == '1' ) {
		try {
			$sinonims = new SC_Sinonims();
			$r = $sinonims->get_lletra($lletra);

			$canonical = $r->canonical;
			$title = $r->title;
			$content_title = $r->content_title;
			$prefix_description = 'Sinònims que comencen per «' . $lletra . '» en català. ';
			$context_holder['sinonims_result'] = $r->html;
		} catch ( Exception $e ) {
			throw_service_error( $content_title, '', true );
		}
	} else {
		throw_error('404', 'No Results For This Search');
		$context_holder['cerca_result'] = 'Esteu utilitzant la cerca per lletra. Heu cercat <strong>'. $context['lletra'] . '</strong>. La cerca només pot contenir una lletra';
	}
}

$context_overrides = array( 'title' => $title, 'prefix_description' => $prefix_description, 'canonical' => $canonical );

$context_filterer = new SC_ContextFilterer( $context_holder );

$context = $context_filterer->get_filtered_context( $context_overrides, false);

$context['post'] = $timberPost;
$context['paraula'] = $paraula;
$context['lletra'] = $lletra;
$context['content_title'] = $content_title;
$context['credits'] = $timberPost->get_field( 'credits' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['to_email'] = get_option('email_sinonims');

Timber::render( array( 'sinonims.twig' ), $context );
