<?php
/**
 * Template Name: Plantilla text amb menú esquerra
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$post = new TimberPost();
$parent_data = get_page_parent_title( $post->ID );
$context['content_title'] = $parent_data['title'];
$context['page_hierarchy'] = wp_list_subpages($parent_data['id']);
$context['post'] = $post;
Timber::render( array( 'plantilla-text-menuleft.twig' ), $context );

