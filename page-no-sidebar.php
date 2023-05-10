<?php
/**
 * Template Name: Plantilla text sense menú
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$timberPost = new TimberPost();
$context['post'] = $timberPost;
$context['credits'] = $timberPost->get_field( 'credits' );
$context['excludeSidebar'] = true;
$context['customAnalytics'] = empty($timberPost->get_field( 'custom_analytics' )) ? false : $timberPost->get_field( 'custom_analytics' );

$context['breadcrumbs'] = get_breadcrumbs( $timberPost, true );

Timber::render( array( 'page-' . $timberPost->post_name . '.twig', 'page.twig' ), $context );