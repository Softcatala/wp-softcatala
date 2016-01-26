<?php

$templates = array( 'index.twig', 'archive-programa.twig' );

array_unshift( $templates, 'archive-' . get_query_var( 'post_type' ) . '.twig' );

$search = get_query_var('cerca');
$sistema_operatiu = get_query_var( 'sistema_operatiu' );
$categoria_programa = get_query_var( 'categoria_programa' );
$arxivat = get_query_var( 'arxivat' );

if( ! empty( $search ) || ! empty( $sistema_operatiu ) || ! empty( $categoria_programa ) || ! empty( $arxivat ) ) {
    $query['s'] = $search;
    $query['sistema-operatiu-programa'] = $sistema_operatiu;
    $query['categoria-programa'] = $categoria_programa;
    $query['arxivat'] = $arxivat;
    $args = get_post_query_args( 'programa', SearchQueryType::Programa, $query );
    $context['cerca'] = $search;
    $context['selected_filter_so'] = ( isset ( $args['filter_so'] ) ? $args['filter_so'] : '' );
    $context['selected_filter_categoria'] = ( isset ( $args['filter_categoria'] ) ? $args['filter_categoria'] : '' );
    $context['selected_arxivat'] = ( isset ( $args['arxivat'] ) ? $args['arxivat'] : '' );
} else {
    $args = get_post_query_args( 'programa', SearchQueryType::Programa );
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