<?php
/**
 * Template Name: Aparells
 *
 * @package wp-softcatala
 */
//JS and Styles related to the page
wp_enqueue_script( 'sc-js-aparells', get_template_directory_uri() . '/static/js/aparells.js', array('sc-js-main'), '1.0.0', true );
wp_localize_script( 'sc-js-aparells', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

//Template initialization
$templates = array('aparells.twig' );
$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
$parent_data = get_page_parent_title( $post );
$context['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );
$context['links'] = $post->get_field( 'link' );
$context['credits'] = $post->get_field( 'credit' );
$context['parent_title'] = $parent_data['title'];
$context['page_hierarchy'] = wp_list_subpages($parent_data['id']);

//Filters population
$context['categories']['sistemesoperatius'] = Timber::get_terms('so_aparell');
$context['categories']['tipus'] = Timber::get_terms('tipus_aparell');

//Search and filters
$search = get_query_var('cerca');
$sistema_operatiu = get_query_var( 'sistema_operatiu' );
$tipus_aparell = get_query_var( 'tipus_aparell' );

//Stats data
$json_path = ABSPATH."../aparells.json";
$context['stats_aparells'] = json_decode( file_get_contents( $json_path ) );

//Generate $args query
if( ! empty( $search ) || ! empty( $sistema_operatiu ) || ! empty( $tipus_aparell ) ) {
    $query_aparell['s'] = $search;
    $query_aparell['so_aparell'] = $sistema_operatiu;
    $query_aparell['tipus_aparell'] = $tipus_aparell;
    $args = get_post_query_args( 'aparell', SearchQueryType::Aparell, $query_aparell );
    $context['cerca'] = $search;
    $context['selected_filter_so'] = ( isset ( $args['filter_so'] ) ? $args['filter_so'] : '' );
    $context['selected_filter_tipus'] = ( isset ( $args['filter_tipus'] ) ? $args['filter_tipus'] : '' );
} else {
    $args = array( 'post_type' => 'aparell', 'posts_per_page' => -1, 'order' => 'ASC' );
}

$context['aparells'] = Timber::get_posts( $args );

//Posts and pagination
$test = query_posts( $args );

$context['aparells'] = Timber::get_posts( $args );
Timber::render( $templates, $context );