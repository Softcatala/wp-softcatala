<?php
$templates = array( 'archive-esdeveniment.twig' );

$title = 'Esdeveniments: ' . single_term_title('', false). ' - Softcatalà';

$contextFilterer = new SC_ContextFilterer();
$context = $contextFilterer->get_filtered_context( array( 'title' => $title ) );

$post = retrieve_page_data('esdeveniment');
$context['cat_link'] = get_term_link( get_query_var( 'term'), 'esdeveniment_cat' );
$context['post'] = $post;
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['categories']['temes'] = Timber::get_terms( 'esdeveniment_cat' );
$context['filters'] = get_the_event_filters();
$context['selected_filter'] = get_query_var( 'filtre' );
$context['content_title'] = 'Esdeveniments';
if( get_query_var('filtre') ) {
    $filter = get_query_var( 'filtre' );
    $filterdate = get_final_time( $filter );
    $context['selected_filter'] = $filter;
    $date_filter_args = get_post_query_args( SearchQueryType::FilteredDate, $filterdate );
    query_posts($date_filter_args);
    $context['posts'] = Timber::get_posts($date_filter_args);
} else {
    $context['posts'] = Timber::get_posts();
}
$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );
