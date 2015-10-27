<?php
/**
 * The Template for displaying an event
 *
 */

$context = Timber::get_context();
$post = Timber::query_post();
$context['post'] = $post;
$context['links'] = $post->get_field( 'link' );

Timber::render( 'esdeveniment.twig', $context );
