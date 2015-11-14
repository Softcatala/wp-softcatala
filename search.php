<?php
/**
 * Search results page for default and custom types
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

$post_type = get_query_var( 'post_type' );
$post = retrieve_page_data( $post_type );
$search = get_search_query();

$templates = array( 'archive-'.$post_type.'.twig', 'index.twig' );
$context = Timber::get_context();
$context['post'] = $post;
$context['links'] = $post->get_field( 'link' );
$context['title'] = get_search_query();

if( $post_type == 'esdeveniment' ) {
    global $wp_query;

    if( get_query_var('orderasc') ) {
        $context['cat_link'] = get_term_link( get_query_var( 'term'), 'esdeveniment_cat' );
    }
    
    $search_args = get_post_query_args( SearchQueryType::Search, $search );
    $args = wp_parse_args( $search_args, $wp_query->query );

    $context['posts'] = Timber::get_posts($args);
    $context['categories']['temes'] = Timber::get_terms( 'esdeveniment_cat' );
    $context['filters'] = get_the_event_filters();
} else {
    $context['posts'] = Timber::get_posts();
    $context['categories']['temes'] = Timber::get_terms('category', array('parent' => get_category_id('temes')));
    $context['categories']['tipus'] = Timber::get_terms('category', array('parent' => get_category_id('tipus')));
}

$context['cerca'] = get_search_query();
$context['pagination'] = Timber::get_pagination();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');

Timber::render( $templates, $context );