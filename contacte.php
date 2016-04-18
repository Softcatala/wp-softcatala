<?php
/**
 * Template Name: Contacte
 *
 * @package wp-softcatala
 */
 wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
 wp_localize_script( 'sc-js-contacte', 'scajax', array(
     'ajax_url' => admin_url( 'admin-ajax.php' )
 ));

$context = Timber::get_context();
$post = new TimberPost();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post'] = $post;
$context['links'] = $post->get_field( 'link' );
$context['credits'] = $post->get_field( 'credit' );

//Contact Form Data
$context['contact']['to_email'] = 'web@softcatala.org';
$context['contact']['nom_from'] = 'Web de Softcatalà';
$context['contact']['assumpte'] = 'Contacte des del formulari general';

Timber::render( array( 'contacte.twig' ), $context );
