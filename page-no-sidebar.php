<?php
/**
 * Template Name: Plantilla text sense menú
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
$context['credits'] = $post->get_field( 'credits' );
$context['excludeSidebar'] = true;
$context['customAnalytics'] = empty($post->get_field( 'custom_analytics' )) ? false : $post->get_field( 'custom_analytics' );
Timber::render( array( 'page-' . $post->post_name . '.twig', 'page.twig' ), $context );