<?php
/**
 * Template Name: Plantilla text amb índex
 *
 * Long pages (guides, documentation): no sidebar widgets; the right column is
 * the page's index, built from its second-level headings that have an HTML
 * anchor. Without any, the text takes the full width.
 *
 * @package wp-softcatala
 */

use Softcatala\Content\TableOfContents;

$context = Timber::context();
$timberPost = Timber::get_post();
$context['post'] = $timberPost;
$context['credits'] = $timberPost->meta( 'credits' );
$context['toc'] = TableOfContents::from_content( $timberPost->post_content );
$context['excludeSidebar'] = true;
$context['customAnalytics'] = empty($timberPost->meta( 'custom_analytics' )) ? false : $timberPost->meta( 'custom_analytics' );

$context['breadcrumbs'] = get_breadcrumbs( $timberPost, true );

Timber::render( array( 'page-' . $timberPost->post_name . '.twig', 'page.twig' ), $context );
