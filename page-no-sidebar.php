<?php
/**
 * Template Name: Plantilla text sense menú
 *
 * @package wp-softcatala
 */

$context = Timber::context();
$timberPost = Timber::get_post();
$context['post'] = $timberPost;
$context['credits'] = $timberPost->meta( 'credits' );
$context['excludeSidebar'] = true;
$context['customAnalytics'] = empty($timberPost->meta( 'custom_analytics' )) ? false : $timberPost->meta( 'custom_analytics' );

$context['breadcrumbs'] = get_breadcrumbs( $timberPost, true );

Timber::render( array( 'page-' . $timberPost->post_name . '.twig', 'page.twig' ), $context );