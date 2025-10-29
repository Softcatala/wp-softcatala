<?php
/**
 * Template Name: Plantilla text amb menú dreta
 *
 * @package wp-softcatala
 */

$context = Timber::context();
$timberPost = Timber::get_post();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post'] = $timberPost;
$context['credits'] = $timberPost->meta( 'credits' );
$context['customAnalytics'] = empty($timberPost->meta( 'custom_analytics' )) ? false : $timberPost->meta( 'custom_analytics' );
$context['breadcrumbs'] = get_breadcrumbs( $timberPost );


Timber::render( array( 'page-' . $timberPost->post_name . '.twig', 'page.twig' ), $context );