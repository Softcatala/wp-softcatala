<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), '1.0.0', true );
wp_enqueue_script( 'sc-js-programes', get_template_directory_uri() . '/static/js/programes.js', array('sc-js-main'), '1.0.0', true );
wp_localize_script( 'sc-js-programes', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$context = Timber::get_context();
$post = Timber::query_post();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post'] = $post;
$context['comment_form'] = TimberHelper::get_comment_form();
$post_links = types_child_posts('link', $post->ID);
$context['links'] = $post->get_field( 'link' );
$context['credits'] = $post->get_field( 'credit' );
$baixades = $post->get_field( 'baixada' );

//Contact Form
$context['contact']['to_email'] = get_option('email_rebost');


//Add program form data
$context['categories']['sistemes_operatius'] = Timber::get_terms( 'sistema-operatiu-programa' );
$context['categories']['categories_programes'] = Timber::get_terms( 'categoria-programa' );
$context['categories']['llicencies'] = Timber::get_terms('llicencia');

//Download count
$download_full = json_decode(file_get_contents(ABSPATH.'../full.json'), true);
$index = array_search($post->idrebost, array_column($download_full, 'idrebost'));
$context['total_downloads'] = $download_full[$index]['total'];
$context['baixades'] = generate_url_download( $baixades, $post );

$context['credits'] = $post->get_field( 'credit' );
$query = array ( 'post_id' => $post->ID );
$args = get_post_query_args( 'page', SearchQueryType::PagePrograma, $query );
query_posts($args);
$context['related_pages'] = Timber::get_posts($args);
$context['projecte_relacionat_url'] = false; //Aquí posarem l'url del projecte relacionat per enllaçar-ho des de la pàgina del programa

if ( post_password_required( $post->ID ) ) {
    Timber::render( 'single-password.twig', $context );
} else {
    Timber::render( array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context );
}