<?php
/**
 * Template Name: Home Softcatala
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$context['slides'] = Timber::get_posts(array('post_type' => 'slide'));
$args = array('post_type' => 'post', 'numberposts' => '3');
$context['posts'] = Timber::get_posts($args);
$args = get_post_query_args( 'esdeveniment', SearchQueryType::Highlight );
add_filter('posts_orderby','orderbyreplace');
query_posts($args);
$context['esdeveniments'] = Timber::get_posts($args);
remove_filter('posts_orderby','orderbyreplace');
$context['programari'] = getProgramari();
Timber::render( array( 'home-sc.twig' ), $context );