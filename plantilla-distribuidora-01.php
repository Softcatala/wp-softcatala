<?php
/**
 * Template Name: Distribuïdora blocs verticals
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
$context['links'] = $post->get_field( 'distribuidora' );
Timber::render( array( 'plantilla-distribuidora-01.twig' ), $context );