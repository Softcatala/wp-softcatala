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

$templates = array( 'archive-podcast-programa.twig' );

$context['title'] = single_term_title('', false);

//Context initialization
$context_filterer = new SC_ContextFilterer();
$context_overrides = array( 'title' => $title );
$context = $context_filterer->get_filtered_context( $context_overrides, false );


$context['term'] = Timber::get_term();
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );



