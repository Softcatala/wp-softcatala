<?php
/**
 * Template Name: Traductor Softcatala
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
Timber::render( array( 'traductor.twig' ), $context );