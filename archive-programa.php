<?php

$templates = array( 'index.twig', 'archive-programa.twig' );

array_unshift( $templates, 'archive-' . get_query_var( 'post_type' ) . '.twig' );
$context['content_title'] = 'Programes i aplicacions';
$post = retrieve_page_data(get_query_var( 'post_type' ));
$context['categories']['sistemes_operatius'] = Timber::get_terms( 'sistema-operatiu-programa' );
$context['categories']['categories_programes'] = Timber::get_terms( 'categoria-programa' );
$context['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );
$context['post'] = $post;
$args = get_post_query_args( 'programa', SearchQueryType::Programa );

$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');

query_posts($args);
$context['posts'] = Timber::get_posts($args);
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );