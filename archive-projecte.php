<?php
/**
 * Archive page for programa custom post type
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), '1.0.0', true );

$title = 'Projectes - Softcatalà';
$description = 'Projectes de traducció o propis que Softcatalà ha desenvolupat per a contribuir a la millora del català a les noves tecnologies';
//Context initialization
$context_filterer = new SC_ContextFilterer();
$context_overrides = array( 'title' => $title, 'description' => $description, 'canonical' => '' );
$context = $context_filterer->get_filtered_context( $context_overrides, false );

$templates = array( 'archive-projecte.twig' );
$post_type = get_query_var( 'post_type' );
$post = retrieve_page_data( $post_type );
$post ? $context['links'] = $post->get_field( 'link' ) : '';
$context['post'] = $post;
$context['content_title'] = 'Projectes';
$context['post_type'] = $post_type;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );

//Contact Form Data
$context['contact']['to_email'] = 'web@softcatala.org';
$context['contact']['nom_from'] = 'Projectes de Softcatalà';
$context['contact']['assumpte'] = '[Projectes] Contacte des del formulari';

//Posts and pagination
$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );