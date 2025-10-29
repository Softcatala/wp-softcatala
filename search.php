<?php
/**
 * Search results page for global search
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
//Apply filter to search only for specific post types
function search_post_types($query) {
    $post_type = array( 'page', 'post', 'projecte', 'programa', 'esdeveniment' );
    if (!$post_type) {
        $post_type = 'any';
    }
    if ($query->is_search) {
        $query->set('post_type', $post_type);
    };
    return $query;
};
add_filter('pre_get_posts','search_post_types');

//JS and Styles related to the page
wp_enqueue_script( 'sc-js-search', get_template_directory_uri() . '/static/js/search.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );

//Template initialization
$templates = array( 'global-search.twig' );
$context = Timber::context();
$search = get_search_query();
$context['title'] = $search;
$context['content_title'] = 'Resultats de cerca';
$context['cerca'] = $search;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');

//Prepare $args and search
global $wp_query;
$args = $wp_query->query_vars;
query_posts( $args );
$context['posts'] = Timber::get_posts($args);

Timber::render( $templates, $context );
