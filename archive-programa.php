<?php

wp_enqueue_script( 'sc-js-programes', get_template_directory_uri() . '/static/js/programes.js', array('sc-js-main'), '1.0.0', true );
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

    foreach( $baixades_posts as $baixada_post ) {
        $programes_baixades_ids[] = wpcf_pr_post_get_belongs($baixada_post->ID, 'programa');
    }

    if( isset( $args ) ) {
        $programes_no_so = get_posts( $args );
        $programes_no_so_ids = array_map("extract_post_ids", $programes_no_so);
        $programes_ids = array_intersect( $programes_no_so_ids, $programes_baixades_ids);
    } else {
        $programes_ids = $programes_baixades_ids;
    }
    $query['post__in'] = $programes_ids;

    $args = get_post_query_args( 'programa', SearchQueryType::Programa, $query );
    $context['selected_filter_so'] = $sistema_operatiu;
}

$context['content_title'] = 'Programes i aplicacions';
$post = retrieve_page_data(get_query_var( 'post_type' ));
$context['categories']['sistemes_operatius'] = Timber::get_terms( 'sistema-operatiu-programa' );
$context['categories']['categories_programes'] = Timber::get_terms( 'categoria-programa' );
$context['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );
$context['post'] = $post;

$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');

query_posts($args);
$context['posts'] = Timber::get_posts($args);
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );