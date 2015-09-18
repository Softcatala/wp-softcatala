<?php
/**
 * Search results page
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

$templates = array( 'search.twig', 'archive.twig', 'index.twig' );
$context = Timber::get_context();
$context['title'] = get_search_query();
$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();
$context['categories']['temes'] = Timber::get_terms('category', array('parent' => getCategoryId('temes')));
$context['categories']['tipus'] = Timber::get_terms('category', array('parent' => getCategoryId('tipus')));

Timber::render( $templates, $context );


/**
 * Function to get the category ID given a category slug
 *
 * @param $slug
 * @return $int
*/
function getCategoryId( $slug ) {
	$category = get_category_by_slug($slug);
	$category_id = $category->term_id; 
	return $category_id;
}