<?php
/**
 * Search results page for global search
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
die('test');
$search = get_search_query();

$templates = array( 'archive-'.$post_type.'.twig', 'index.twig' );
$context = Timber::get_context();
$context['post'] = $post;
$context['links'] = $post->get_field( 'link' );
$context['title'] = get_search_query();

$context['content_title'] = 'Notícies';
$context['cat_link'] = get_category_link( get_query_var('cat') );
$context['posts'] = Timber::get_posts();
$context['categories']['temes'] = Timber::get_terms('category', array('parent' => get_category_id('temes')));
$context['categories']['tipus'] = Timber::get_terms('category', array('parent' => get_category_id('tipus')));

$context['cerca'] = get_search_query();
$context['pagination'] = Timber::get_pagination();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');

Timber::render( $templates, $context );