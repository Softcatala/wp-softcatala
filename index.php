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
wp_enqueue_script( 'sc-js-noticies', get_template_directory_uri() . '/static/js/noticies.js', array('sc-js-main'), '1.0.0', true );

$context = Timber::get_context();

//Cerca
$search = get_query_var('cerca');
$tipus = get_query_var( 'tipus' );
$tema = get_query_var( 'tema' );


if( ! empty( $search ) || ! empty( $tipus ) || ! empty( $tema ) ) {
	$query['s'] = $search;
	$context['cerca'] = $search;
	$context['selected_tipus'] = $tipus;
	$context['selected_tema'] = $tema;
	$query['categoria'] = array();
	if ( $tema ) {
		$tema_cat = get_category_by_slug( $tema );
		$query['categoria'][] = $tema_cat->term_id;
	}
	if ( $tipus ) {
		$tipus_cat = get_category_by_slug( $tipus );
		$query['categoria'][] = $tipus_cat->term_id;
	}

	$args = get_post_query_args( 'post', SearchQueryType::Post, $query );
} else {
	$args = get_post_query_args( 'post', SearchQueryType::Post );
}

$post = Timber::query_post(get_option( 'page_for_posts' ));
$context['post'] = $post;
$context['title'] = get_search_query();
$context['content_title'] = 'Notícies';
query_posts($args);
$context['posts'] = Timber::get_posts($args);
$context['pagination'] = Timber::get_pagination();
$context['cerca'] = get_search_query();
$context['categories']['temes'] = Timber::get_terms('category', array('parent' => get_category_id('temes')));
$context['categories']['tipus'] = Timber::get_terms('category', array('parent' => get_category_id('tipus')));
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');


$templates = array( 'index.twig' );
if ( is_home() ) {
	array_unshift( $templates, 'home.twig' );
}

Timber::render( $templates, $context );