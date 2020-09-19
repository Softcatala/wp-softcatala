<?php
/**
 * The template for displaying Archive pages, for native and custom post_types
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

$templates = array( 'archive-podcast.twig' );

$context['post'] = $post;



$context['posts'] = Timber::get_posts();


Timber::render( $templates, $context );



/* Functions */

