<?php
/**
 * The Template for displaying an event
 *
 */

$context = Timber::get_context();
$post = Timber::query_post();
$context['post'] = $post;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['links'] = $post->get_field( 'link' );

Timber::render( 'esdeveniment.twig', $context );
