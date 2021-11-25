<?php
/**
 * Archive page for esdeveniment custom post type
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page



//Template initialization
$templates = array('archive-dadesobertes.twig' );

$title = 'Dades Obertes - Softcatalà';
$description = 'Respositori de dades obertes';
$post = Timber::get_post();

//Context initialization
$context_filterer = new SC_ContextFilterer();
$context_overrides = array( 'title' => $title, 'description' => $description );
$context = $context_filterer->get_filtered_context( $context_overrides, false );
$context['content_title'] = 'Dades Obertes';
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['posts'] = Softcatala\Providers\Dadesobertes::get();
$context['pagination'] = Timber::get_pagination();
$context['post'] = $post;
$context['share_title'] = $context['content_title'];

Timber::render( $templates, $context );
