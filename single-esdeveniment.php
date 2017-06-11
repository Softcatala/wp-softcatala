<?php
/**
 * The Template for displaying an event
 *
 *  @package  wp-softcatala
 */

$context = Timber::get_context();
$post = Timber::query_post();
$context['post'] = $post;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['credits'] = $post->get_field( 'credits' );

Timber::render( 'esdeveniment.twig', $context );
