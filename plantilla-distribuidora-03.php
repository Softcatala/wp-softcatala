<?php
/**
 * Template Name: Distribuïdora text i blocs verticals
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
$context['links'] = $post->get_field( 'distribuidora' );
Timber::render( array( 'plantilla-distribuidora-03.twig' ), $context );