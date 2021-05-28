<?php
/**
 * Template Name: Distribuïdora amb capçalera/enllaços
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$timberPost = new TimberPost();
$context['post'] = $timberPost;
$context['links'] = $timberPost->get_field( 'link' );
Timber::render( array( 'plantilla-distribuidora-02.twig' ), $context );