<?php
/**
 * Archive page for esdeveniment custom post type
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page
wp_enqueue_script( 'sc-js-esdeveniments', get_template_directory_uri() . '/static/js/esdeveniments.js', array('sc-js-main'), '1.0.0', true );

//Template initialization
$templates = array('archive-esdeveniment.twig' );

//Search and filters
$search = get_query_var('cerca');
$tema = get_query_var('tema');
$filter = get_query_var( 'data' );
$filterdate = get_final_time( $filter );

//Generate $args query
if( ! empty( $search ) || ! empty( $tema ) || ! empty( $filter ) ) {
    $search_args = get_post_query_args( 'esdeveniment', SearchQueryType::Search, $search );
    $args = wp_parse_args( $search_args, $wp_query->query ); //search + active args

    $date_filter_args = get_post_query_args( 'esdeveniment', SearchQueryType::FilteredDate, $filterdate );
    $args = wp_parse_args( $date_filter_args, $args ); //all filters applied

    $date_filter_args = get_post_query_args( 'esdeveniment', SearchQueryType::FilteredTema, $tema );
    $args = wp_parse_args( $date_filter_args, $args ); //all filters applied

    $title = 'Esdeveniments - ';
    (!empty( $search ) ? $title .= 'cerca: ' . $search . ' - ' : '');
    (!empty( $tema ) ? $title .= 'tema: ' . get_term_name_by_slug ($tema , 'esdeveniment_cat' ) . ' - ' : '');
    (!empty( $filter ) ? $title .= 'data: ' . get_the_filter_date_name( $filter ) . ' - ' : '');
    $title .= 'Softcatalà';

} else {
    $title = 'Esdeveniments - Softcatalà';
    $description = 'Esdeveniments relacionats amb el món de la tecnologia i el català.';
    $args = $wp_query->query;
}

//Context initialization
$context_filterer = new SC_ContextFilterer();
$context_overrides = array( 'title' => $title, 'description' => $description, 'canonical' => $canonical );
$context = $context_filterer->get_filtered_context( $context_overrides, false );

$post = Timber::query_post(get_option( 'page_for_posts' ));
$context['post'] = $post;
$context['content_title'] = 'Esdeveniments';
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');

//Filters population
$context['cat_link'] = get_category_link( get_query_var('esdeveniment_cat') );
$context['categories']['temes'] = Timber::get_terms( 'esdeveniment_cat' );
$context['filters'] = get_the_event_filters();
$context['selected_filter_tema'] = $tema;
$context['selected_filter_data'] = $filter;
$context['cerca'] = $search;

//Posts and pagination
query_posts( $args );
$context['posts'] = Timber::get_posts( $args );
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );