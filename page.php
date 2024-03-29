<?php
/**
 * Template Name: Plantilla text amb menú dreta
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$timberPost = new TimberPost();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post'] = $timberPost;
$context['credits'] = $timberPost->get_field( 'credits' );
$context['customAnalytics'] = empty($timberPost->get_field( 'custom_analytics' )) ? false : $timberPost->get_field( 'custom_analytics' );
$context['breadcrumbs'] = get_breadcrumbs( $timberPost );


Timber::render( array( 'page-' . $timberPost->post_name . '.twig', 'page.twig' ), $context );