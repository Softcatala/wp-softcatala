<?php
/**
 * Archive page for programa custom post type
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );

$title = 'Projectes - Softcatalà';
$description = 'Projectes de traducció o propis que Softcatalà ha desenvolupat per a contribuir a la millora del català a les noves tecnologies';

$templates = array( 'archive-projecte.twig' );
$post_type = get_query_var( 'post_type' );
$post = retrieve_page_data( $post_type );
$post ? $context_holder['links'] = $post->get_field( 'link' ) : '';
$context_holder['post'] = $post;
$context_holder['content_title'] = 'Projectes';
$context_holder['post_type'] = $post_type;
$context_holder['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context_holder['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context_holder['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );

//Contact Form Data
$context_holder['contact']['to_email'] = 'web@softcatala.org';
$context_holder['contact']['nom_from'] = 'Projectes de Softcatalà';
$context_holder['contact']['assumpte'] = '[Projectes] Contacte des del formulari';

//Posts and pagination
$context_holder['posts'] = $sc_types['projectes']->get_sorted_projects();
$context_holder['pagination'] = Timber::get_pagination();

//Context initialization
$context_filterer = new SC_ContextFilterer( $context_holder );
$context_overrides = array( 'title' => $title, 'description' => $description );
$context = $context_filterer->get_filtered_context( $context_overrides, false );

Timber::render( $templates, $context );
