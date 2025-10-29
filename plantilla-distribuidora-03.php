<?php
/**
 * Template Name: Distribuïdora text i blocs verticals
 *
 * @package wp-softcatala
 */

$context = Timber::context();
$timberPost = Timber::get_post();
$context['post'] = $timberPost;
$context['links'] = $timberPost->meta( 'distribuidora' );
Timber::render( array( 'plantilla-distribuidora-03.twig' ), $context );