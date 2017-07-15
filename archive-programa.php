<?php
/**
 * Archive page for programa custom post type
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'jquery-browser-plugin', get_template_directory_uri() . '/static/js/jquery.browser.min.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-programes', get_template_directory_uri() . '/static/js/programes.js', array('sc-js-main', 'jquery-browser-plugin'), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-programes', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$cpt_programa = \Softcatala\TypeRegisters\Programa::getInstance();

//Template initialization
$templates = array( 'archive-programa.twig' );

$post = $cpt_programa->get_page();
$post ? $context_holder['links'] = $post->get_field( 'link' ) : '';
$context_holder['post'] = $post;
$context_holder['content_title'] = 'Programes i aplicacions';
$context_holder['post_type'] = $cpt_programa->singular;
$context_holder['conditions_text'] = $cpt_programa->condicions_afegir_programa();
$context_holder['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context_holder['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context_holder['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );

//Filters population
$context_holder['categories']['sistemes_operatius'] = Timber::get_terms( 'sistema-operatiu-programa' );
$context_holder['categories']['categories_programes'] = Timber::get_terms( 'categoria-programa' );
$context_holder['categories']['llicencies'] = Timber::get_terms('llicencia');

//Search and filters
$search = urldecode( get_query_var( 'cerca' ));
$sistema_operatiu = get_query_var( 'sistema_operatiu' );
$categoria_programa = get_query_var( 'categoria_programa' );

//Generate $args query
$flag_search = false;
$description = 'Programari / Software en català / valencià';
if( ! empty( $search ) || ! empty( $categoria_programa ) || ! empty( $sistema_operatiu ) ) {
    $flag_search = true;
    $query['s'] = $search;
    $query['categoria-programa'] = $categoria_programa;
    $query['sistema-operatiu-programa'] = $sistema_operatiu;
    $args = get_post_query_args( 'programa', SearchQueryType::Programa, $query );

    //Selected values
    $context_holder['cerca'] = $search;
    $context_holder['selected_filter_categoria'] = ( isset ( $args['filter_categoria'] ) ? $args['filter_categoria'] : '' );
    $context_holder['selected_filter_so'] = ( isset ( $args['filter_sistema_operatiu'] ) ? $args['filter_sistema_operatiu'] : '' );

    $title = 'Programes - ';
    (!empty( $search ) ? $title .= 'cerca: ' . $search . ' - ' : '');
    (!empty( $categoria_programa ) ? $title .= 'categoria: ' . $categoria_programa . ' - ' : '');
    (!empty( $sistema_operatiu ) ? $title .= 'sistema operatiu: ' . $sistema_operatiu . ' - ' : '');
    $title .= 'Softcatalà';
} elseif ( ! isset ( $args ) ) {
    $title = 'Programes - Softcatalà';
    $args = get_post_query_args( 'programa', SearchQueryType::Programa );
}

//Posts and pagination
query_posts( $args );
$context_holder['posts'] = Timber::get_posts( $args );
$context_holder['pagination'] = Timber::get_pagination();

if (count($context_holder['posts']) == 0 && $flag_search == true ) {
    throw_error( '404', 'No programs found' );
}

//Context initialization
$context_filterer = new SC_ContextFilterer( $context_holder );
$context_overrides = array( 'title' => $title, 'description' => $description );
$context = $context_filterer->get_filtered_context( $context_overrides, false );

Timber::render( $templates, $context );
