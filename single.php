<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::get_context();
$post = Timber::query_post();
$context['post'] = $post;
$context['comment_form'] = TimberHelper::get_comment_form();
$post_links = types_child_posts('link', $post->ID);
$links = array();
foreach ($post_links as $k => $post_link) {
	$links[]['link_title'] = $post_link->fields['link_title'];
	$links[]['link_url'] = $post_link->fields['link_url'];
	$links[]['link_description'] = $post_link->fields['link_description'];  
}

$context['links'] = $links;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');

if ( post_password_required( $post->ID ) ) {
	Timber::render( 'single-password.twig', $context );
} else {
	Timber::render( array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context );
}
