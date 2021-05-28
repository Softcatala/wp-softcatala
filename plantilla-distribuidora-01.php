<?php
/**
 * Template Name: Distribuïdora blocs verticals
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$timberPost = new TimberPost();
$context['post'] = $timberPost;
$context['links'] = $timberPost->get_field( 'distribuidora' );
Timber::render( array( 'plantilla-distribuidora-01.twig' ), $context );