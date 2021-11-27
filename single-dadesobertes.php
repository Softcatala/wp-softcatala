<?php
/**
 * The Template for displaying an dada oberta
 *
 *  @package  wp-softcatala
 */

$context = Timber::get_context();
$post = Timber::query_post();
$context['post'] = $post;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['creators'] = get_field( 'creator' );
Timber::render( 'single-dadesobertes.twig', $context );