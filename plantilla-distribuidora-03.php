<?php
/**
 * Template Name: Distribuïdora text i blocs verticals
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$timberPost = new TimberPost();
$context['post'] = $timberPost;
$context['links'] = $timberPost->get_field( 'distribuidora' );
Timber::render( array( 'plantilla-distribuidora-03.twig' ), $context );