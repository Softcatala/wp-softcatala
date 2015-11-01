<?php

$templates = array( 'archive-esdeveniment.twig' );

$context = Timber::get_context();

$post = retrieve_page_data('esdeveniment');
$context['cat_link'] = get_term_link( get_query_var( 'term'), 'esdeveniment_cat' );
$context['post'] = $post;
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['categories']['temes'] = Timber::get_terms( 'esdeveniment_cat' );
$context['filtres'] = get_the_event_filters();
$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );
