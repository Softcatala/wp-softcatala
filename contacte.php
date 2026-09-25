<?php
/**
 * Template Name: Contacte
 *
 * @package wp-softcatala
 */
 wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array( 'jquery' ), WP_SOFTCATALA_VERSION, true );

$context = Timber::context();
$timberPost = Timber::get_post();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post'] = $timberPost;
$context['credits'] = $timberPost->meta( 'credits' );

Timber::render( array( 'contacte.twig' ), $context );
