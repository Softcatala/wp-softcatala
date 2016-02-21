<?php
/**
 * Archive page for programa custom post type
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page


//Template initialization
$templates = array( 'archive-projecte.twig' );
$context = Timber::get_context();
$post_type = get_query_var( 'post_type' );
$post = retrieve_page_data( $post_type );
$post ? $context['links'] = $post->get_field( 'link' ) : '';
$context['post'] = $post;
$context['content_title'] = 'Projectes';
$context['post_type'] = $post_type;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );

//Posts and pagination
$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );