<?php

wp_enqueue_script( 'sc-js-programes', get_template_directory_uri() . '/static/js/programes.js', array('sc-js-main'), '1.0.0', true );
wp_localize_script( 'sc-js-programes', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));
$templates = array( 'archive-programa.twig' );

$search = get_query_var('cerca');
$sistema_operatiu = get_query_var( 'sistema_operatiu' );
$categoria_programa = get_query_var( 'categoria_programa' );
$arxivat = get_query_var( 'arxivat' );

if( ! empty( $search ) || ! empty( $categoria_programa ) || ! empty( $arxivat ) ) {
    $query['s'] = $search;
    $query['categoria-programa'] = $categoria_programa;
    $query['arxivat'] = $arxivat;
    $args = get_post_query_args( 'programa', SearchQueryType::Programa, $query );
    $context['cerca'] = $search;
    $context['selected_filter_categoria'] = ( isset ( $args['filter_categoria'] ) ? $args['filter_categoria'] : '' );
    $context['selected_arxivat'] = ( isset ( $args['arxivat'] ) ? $args['arxivat'] : '' );
} else {
    $args = get_post_query_args( 'programa', SearchQueryType::Programa );
}

if( ! empty( $sistema_operatiu ) ) {
    $query['sistema-operatiu-programa'] = $sistema_operatiu;

    $args_so_baixades = array (
        'post_type' => 'baixada',
        'tax_query' => array (
            array(
                'taxonomy' => 'sistema-operatiu-programa',
                'field' => 'slug',
                'terms' => $sistema_operatiu,
                'post_status'    => 'publish'
            )
        )
    );
    $baixades_posts = get_posts( $args_so_baixades );
    $programes_baixades_ids = array_map( "extract_post_ids_program", $baixades_posts );

    if( isset( $args ) ) {
        $all_programs = get_posts( $args );
        $all_programs_ids = array_map("extract_post_ids", $all_programs);
        $programes_ids = array_intersect( $all_programs_ids, $programes_baixades_ids);
    } else {
        $programes_ids = $programes_baixades_ids;
    }
    $query['post__in'] = $programes_ids;

    $args = get_post_query_args( 'programa', SearchQueryType::Programa, $query );
    $context['selected_filter_so'] = $sistema_operatiu;
}
$post_type = get_query_var( 'post_type' );
$context['content_title'] = 'Programes i aplicacions';
$post = retrieve_page_data( $post_type );
$context['categories']['sistemes_operatius'] = Timber::get_terms( 'sistema-operatiu-programa' );
$context['categories']['categories_programes'] = Timber::get_terms( 'categoria-programa' );
$context['categories']['llicencies'] = Timber::get_terms('llicencia');
$context['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );
$context['post'] = $post;
$context['post_type'] = $post_type;
$context['conditions_text'] = "Si voleu afegir un programa nou...";

if( $post ) {
    $context['links'] = $post->get_field( 'link' );
}
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');

query_posts($args);
$context['posts'] = Timber::get_posts($args);
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );