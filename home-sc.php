<?php
/**
 * Template Name: Home Softcatala
 *
 * @package wp-softcatala
 */

//Template initialization
$templates = array('home-sc.twig' );
$context = Timber::get_context();
$context['ads_container'] = true;

//Sections population
$context['slides'] = Timber::get_posts( array( 'post_type' => 'slider' ) );
$args = array( 'post_type' => 'post', 'numberposts' => '3', 'post_status' => 'publish' );
$context['posts'] = Timber::get_posts($args);
$args = get_post_query_args( 'esdeveniment', SearchQueryType::Highlight );
add_filter('posts_orderby','orderbyreplace');
query_posts($args);
$context['esdeveniments'] = Timber::get_posts($args);
remove_filter('posts_orderby','orderbyreplace');
$context['programari'] = get_top_downloads_home();
Timber::render( $templates, $context );