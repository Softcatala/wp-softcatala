<?php
/**
 * The main template file
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

$context = Timber::get_context();
$post = Timber::query_post(get_option( 'page_for_posts' ));
$context['post'] = $post;
$context['title'] = get_search_query();
$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();
$context['cerca'] = get_search_query();
$context['categories']['temes'] = Timber::get_terms('category', array('parent' => get_category_id('temes')));
$context['categories']['tipus'] = Timber::get_terms('category', array('parent' => get_category_id('tipus')));
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');

$templates = array( 'index.twig' );
if ( is_home() ) {
	array_unshift( $templates, 'home.twig' );
}

Timber::render( $templates, $context );