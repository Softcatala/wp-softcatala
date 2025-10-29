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
 * @package  wp-softcatala
 */
//JS and Styles related to the page
wp_enqueue_script( 'sc-js-noticies', get_template_directory_uri() . '/static/js/noticies.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-novetats', get_template_directory_uri() . '/static/js/novetats.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-novetats', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

//Template initialization
$templates = array( 'index.twig' );
is_home() ? array_unshift( $templates, 'home.twig' ) : '';
$post = Timber::query_post( get_option( 'page_for_posts' ) );
$context_holder['post'] = $post;
$context_holder['content_title'] = 'Notícies';
$context_holder['links'] = $post->meta( 'link' );
$context_holder['sidebar_top'] = Timber::get_widgets( 'sidebar_top' );
$context_holder['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context_holder['sidebar_bottom'] = Timber::get_widgets( 'sidebar_bottom' );

//Filters population
$context_holder['categories']['temes'] = Timber::get_terms( 'category', array( 'parent' => get_category_id( 'temes' ) ) );
$context_holder['categories']['tipus'] = Timber::get_terms( 'category', array( 'parent' => get_category_id( 'tipus' ) ) );

//Search and filters
$search = stripslashes(get_query_var( 'cerca' ));
$tipus = get_query_var( 'tipus' );
$tema = get_query_var( 'tema' );

if( ! empty( $search ) || ! empty( $tipus ) || ! empty( $tema ) ) {
	$context_holder['cerca'] = $search;
	$context_holder['selected_tipus'] = $tipus;
	$context_holder['selected_tema'] = $tema;
	$context_holder['title'] = $search;

    $query['s'] = $search;
	$query['categoria'] = array();
	if ( $tema ) {
		$tema_cat = get_category_by_slug( $tema );
		$query['categoria'][] = $tema_cat->term_id;
	}
	if ( $tipus ) {
		$tipus_cat = get_category_by_slug( $tipus );
		$query['categoria'][] = $tipus_cat->term_id;
	}

	$title = 'Notícies - ';
	(!empty( $search ) ? $title .= 'cerca: ' . $search . ' - ' : '');
	(!empty( $tipus ) ? $title .= 'tipus: ' . get_term_name_by_slug ($tipus , 'category' ) . ' - ' : '');
	(!empty( $tema ) ? $title .= 'tema: ' . get_term_name_by_slug ($tema , 'category' ) . ' - ' : '');
	$title .= 'Softcatalà';

	$args = get_post_query_args( 'post', SearchQueryType::Post, $query );
} else {
	$title = 'Notícies - Softcatalà';
    $args = $wp_query->query;
}

//Posts and pagination
query_posts( $args );
$context_holder['posts'] = Timber::get_posts( $args );

//Context initialization
$description = 'Notícies de llengua catalana, tecnologia en català.';
$context_filterer = new SC_ContextFilterer( $context_holder );
$context_overrides = array( 'title' => $title, 'description' => $description );
$context = $context_filterer->get_filtered_context( $context_overrides, false );

Timber::render( $templates, $context );
