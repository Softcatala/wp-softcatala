<?php
/**
 * Template Name: Distribuïdora blocs verticals
 *
 * @package wp-softcatala
 */

$context = Timber::context();
$timberPost = Timber::get_post();
$context['post'] = $timberPost;
$context['links'] = $timberPost->meta( 'distribuidora' );
Timber::render( array( 'plantilla-distribuidora-01.twig' ), $context );