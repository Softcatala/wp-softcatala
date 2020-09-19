<?php
/**
 * The template for displaying podcasts programs pages, for native and custom post_types
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */

$templates = array( 'archive-podcast-programa' );

$context = Timber::get_context();
$context['post'] = $post;
$context['title'] = single_term_title('', false);

$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );



