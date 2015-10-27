<?php
/**
 * The template for displaying Archive pages, for native and custom post_types
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.2
 */

$templates = array( 'archive.twig', 'index.twig', 'archive-esdeveniment.twig' );

$context = Timber::get_context();
$post = Timber::query_post(get_option( 'page_for_posts' ));
$context['post'] = $post;
$context['categories']['temes'] = Timber::get_terms('category', array('parent' => get_category_id('temes')));
$context['categories']['tipus'] = Timber::get_terms('category', array('parent' => get_category_id('tipus')));

$context['title'] = 'Archive';
if ( is_category() ) {
	$context['title'] = single_cat_title( '', false );
	$context['cat_link'] = get_category_link( get_query_var('cat') );
	array_unshift( $templates, 'archive-' . get_query_var( 'cat' ) . '.twig' );
} else if ( is_post_type_archive() ) {
	$context['title'] = post_type_archive_title( '', false );
	array_unshift( $templates, 'archive-' . get_query_var( 'post_type' ) . '.twig' );

	$post = retrieve_page_data(get_query_var( 'post_type' ));
	$context['post'] = $post;
    $context['cat_link'] = get_category_link( get_query_var('esdeveniment_cat') );
    $context['categories']['temes'] = Timber::get_terms( 'esdeveniment_cat' );
    $context['filtres'] = get_the_event_filters();
    $filtre = get_query_var( 'filtre' );
    $filtredate = get_final_time( $filtre );
}

$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
//Get the posts depending on the parameters
if(isset($filtre)) {
    $context['selected_filtre'] = $filtre;
    $args = get_post_query_args( $filtre, $filtredate );
    query_posts($args);
    $context['posts'] = Timber::get_posts($args);
} else {
    $context['posts'] = Timber::get_posts();
}
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );



/* Functions */

/*
 * Returns the start_time and final_time of the time range in UNIX Timestamp
 */
function get_final_time( $filtre )
{
    $today_unix_time = time();
    switch ($filtre) {
        case 'setmana':
            $filtredate['start_time'] = time();
            $filtredate['final_time'] = 60*60*24*7 + $today_unix_time;
            break;
        case '1mes':
            $filtredate['start_time'] = time();
            $filtredate['final_time'] = strtotime("first day of next month");
            break;
        case 'setmanavinent':
            $filtredate['start_time'] = strtotime("monday next week");
            $filtredate['final_time'] = strtotime("sunday next week");
            break;
        default:
            $filtredate['start_time'] = time();
            $filtredate['final_time'] = 60*60*24*700 + $today_unix_time;
            break;
    }

    return $filtredate;
}