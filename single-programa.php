<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

wp_enqueue_script( 'sc-js-programes', get_template_directory_uri() . '/static/js/programes.js', array('sc-js-main'), '1.0.0', true );
wp_localize_script( 'sc-js-programes', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$context = Timber::get_context();
$post = Timber::query_post();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post'] = $post;

//Download count
$download_full = json_decode(file_get_contents('http://softcatala.local/full.json'), true);
$index = array_search($post->idrebost, array_column($download_full, 'idrebost'));
$context['total_downloads'] = $download_full[$index]['total'];

$context['comment_form'] = TimberHelper::get_comment_form();
$post_links = types_child_posts('link', $post->ID);
$context['links'] = $post->get_field( 'link' );
$context['baixades'] = $post->get_field( 'baixada' );
$context['baixades_urls'] = generate_url_download( $context['baixades'], $post );
$context['credits'] = $post->get_field( 'credit' );
$query = array ( 'post_id' => $post->ID );
$args = get_post_query_args( 'page', SearchQueryType::PagePrograma, $query );
query_posts($args);
$context['related_pages'] = Timber::get_posts($args);

if ( post_password_required( $post->ID ) ) {
    Timber::render( 'single-password.twig', $context );
} else {
    Timber::render( array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context );
}


function generate_url_download( $baixades, $post ) {
    //https://baixades.softcatala.org/?url=http://download.mozilla.org/?product=firefox-44.0.1&os=linux&lang=ca&id=3522&mirall=&extern=2&versio=44.0.1&so=linux
    foreach ( $baixades as $key => $baixada ) {
        //OS
        $term_list = wp_get_post_terms($baixada->ID, 'sistema-operatiu-programa', array("fields" => "all"));
        if ( $term_list ) {
            $os = $term_list[0]->name;
        } else {
            $os = '';
        }

        $download_url[$key]['url'] = 'https://baixades.softcatala.org/';
        $download_url[$key]['url'] .= '?url='.$baixada->url_baixada;
        $download_url[$key]['url'] .= '&os='.$os;
        $download_url[$key]['url'] .= '&id='.$post->idrebost;
        $download_url[$key]['url'] .= '&versio='.$baixada->versio_baixada;
        $download_url[$key]['url'] .= '&so='.$os;

        $download_url[$key]['url']['ID'] = $baixada->ID;
    }
}