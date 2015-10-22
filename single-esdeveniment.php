<?php
/**
 * The Template for displaying an event
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::get_context();
$post = Timber::query_post();
$context['post'] = $post;
$post_links = types_child_posts('link', $post->ID);
$context['links'] = $post->get_field( 'link' );

Timber::render( 'esdeveniment.twig', $context );
