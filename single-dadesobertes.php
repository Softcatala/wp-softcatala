<?php
/**
 * The Template for displaying an dada oberta
 *
 *  @package  wp-softcatala
 */

 /* Estils propis dades obertes */
 wp_enqueue_style( 'sc-css-dades-obertes', get_template_directory_uri() . '/static/css/dades-obertes.css', array('sc-css-main'),WP_SOFTCATALA_VERSION );


$context = Timber::get_context();
$post = Timber::query_post();
$context['post'] = $post;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['creators'] = get_field( 'creator' );
Timber::render( 'single-dadesobertes.twig', $context );