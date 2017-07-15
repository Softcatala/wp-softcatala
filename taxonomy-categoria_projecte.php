<?php
/**
 * Archive page for programa custom post type
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */

use Softcatala\TypeRegisters\Projecte;

$post = retrieve_page_data( 'projecte' );
$post ? $context['links'] = $post->get_field( 'link' ) : '';

$title = 'En què treballem: ' . single_term_title('', false);

$contextFilterer = new SC_ContextFilterer();
$context = $contextFilterer->get_filtered_context( array( 'title' => $title ) );

$context['post'] = $post;
$context['content_title'] = $title;
$context['post_type'] = $post_type;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );

//Posts and pagination
$args = $wp_query->query;

$context['posts'] = Projecte::get_instance()->get_sorted_projects( $args );
$context['pagination'] = Timber::get_pagination();

Timber::render( 'archive-projecte.twig', $context );
