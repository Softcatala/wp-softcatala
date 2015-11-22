<?php
/**
 * Template Name: Distribuïdora amb capçalera/enllaços
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
$context['links'] = $post->get_field( 'link' );
Timber::render( array( 'plantilla-distribuidora-02.twig' ), $context );