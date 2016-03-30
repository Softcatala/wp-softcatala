<?php
/**
 * Archive page for programa custom post type
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), '1.0.0', true );
wp_enqueue_script( 'sc-js-programes', get_template_directory_uri() . '/static/js/programes.js', array('sc-js-main'), '1.0.0', true );
wp_localize_script( 'sc-js-programes', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

//Template initialization
$templates = array( 'archive-programa.twig' );
$context = Timber::get_context();
$post_type = get_query_var( 'post_type' );
$post = retrieve_page_data( $post_type );
$post ? $context['links'] = $post->get_field( 'link' ) : '';
$context['post'] = $post;
$context['content_title'] = 'Programes i aplicacions';
$context['post_type'] = $post_type;
$context['conditions_text'] = get_option( 'sc_text_programes' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );

//Filters population
$context['categories']['sistemes_operatius'] = Timber::get_terms( 'sistema-operatiu-programa' );
$context['categories']['categories_programes'] = Timber::get_terms( 'categoria-programa' );
$context['categories']['llicencies'] = Timber::get_terms('llicencia');

//Contact Form
$context['contact']['to_email'] = get_option('email_rebost');

//Search and filter
$search = urldecode( get_query_var( 'cerca' ));
$sistema_operatiu = get_query_var( 'sistema_operatiu' );
$categoria_programa = get_query_var( 'categoria_programa' );

//Generate $args query
$flag_search = false;
if( ! empty( $search ) || ! empty( $categoria_programa ) || ! empty( $sistema_operatiu ) ) {
    $flag_search = true;
    $query['s'] = $search;
    $query['categoria-programa'] = $categoria_programa;
    $query['sistema_operatiu'] = $sistema_operatiu;
    $args = get_post_query_args( 'programa', SearchQueryType::Programa, $query );

    //Selected values
    $context['cerca'] = $search;
    $context['selected_filter_categoria'] = ( isset ( $args['filter_categoria'] ) ? $args['filter_categoria'] : '' );
    $context['selected_filter_so'] = ( isset ( $args['filter_sistema_operatiu'] ) ? $args['filter_sistema_operatiu'] : '' );
} elseif ( ! isset ( $args ) ) {
    $args = get_post_query_args( 'programa', SearchQueryType::Programa );
}


//Posts and pagination
query_posts( $args );
$context['posts'] = Timber::get_posts( $args );
$context['pagination'] = Timber::get_pagination();

if (count($context['posts']) == 0 && $flag_search == true ) {
    throw_error( '404', 'No programs found' );
}
Timber::render( $templates, $context );
