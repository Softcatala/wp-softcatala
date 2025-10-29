<?php
/**
 * Template Name: Distribuïdora amb capçalera/enllaços
 *
 * @package wp-softcatala
 */

$context = Timber::context();
$timberPost = Timber::get_post();
$context['post'] = $timberPost;
$context['links'] = $timberPost->meta( 'link' );
Timber::render( array( 'plantilla-distribuidora-02.twig' ), $context );