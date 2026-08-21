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
 * @package  wp-softcatala
 */

$templates = array( 'index.twig', 'archive-esdeveniment.twig' );

$term_metadata = false;
if ( is_category() || is_tag() || is_tax() ) {
    $term_metadata = SC_Seo::term_metadata( get_queried_object() );
    $context_filterer = new SC_ContextFilterer();
    $context = $context_filterer->get_filtered_context(
        array(
            'title'       => $term_metadata['title'],
            'description' => $term_metadata['description'],
        )
    );
} else {
    $context = Timber::context();
}

$post = Timber::query_post(get_option( 'page_for_posts' ));
$context['post'] = $post;


if ( is_category() ) {
    $context['categories']['temes'] = Timber::get_terms('category', array('parent' => get_category_id('temes')));
    $context['categories']['tipus'] = Timber::get_terms('category', array('parent' => get_category_id('tipus')));
    $context['content_title'] = $term_metadata['content_title'];
    $context['cat_link'] = get_category_link( get_query_var('cat') );

    array_unshift( $templates, 'archive-' . get_query_var( 'cat' ) . '.twig' );
} else if ( is_tag() || is_tax() ) {
    $context['content_title'] = $term_metadata['content_title'];
} else { //Any other query asking for date parameters will display just news
    $context['categories']['temes'] = Timber::get_terms('category', array('parent' => get_category_id('temes')));
    $context['categories']['tipus'] = Timber::get_terms('category', array('parent' => get_category_id('tipus')));

    if (is_day()){
        $context['content_title'] = 'Arxiu '.get_the_date( 'j F Y' );
    } else if (is_month()){
        $context['content_title'] = 'Arxiu '.get_the_date( 'F Y' );
    } else if (is_year()){
        $context['content_title'] = 'Arxiu '.get_the_date( 'Y' );
    }
}

$context['links'] = $post ? $post->meta( 'link' ) : array();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
//Get the posts depending on the parameters
if( isset( $filter ) ) {
    $context['selected_filter'] = $filter;
    $args = get_post_query_args( 'esdeveniment', SearchQueryType::FilteredDate, $filterdate );
    query_posts($args);
    $context['posts'] = Timber::get_posts($args);
} else {
    $context['posts'] = Timber::get_posts();
}
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );



/* Functions */
