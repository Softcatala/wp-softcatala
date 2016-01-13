<?php
/**
 * Template Name: Sinònims Softcatala OpenThesaurus
 *
 * @package wp-softcatala
 */

/* JS scripts */
wp_enqueue_script( 'sc-js-sinonims', get_template_directory_uri() . '/static/js/sinonims.js', array(), '1.0.0', true );

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
$context['content_title'] = 'Diccionari de sinónims';
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');
Timber::render( array( 'sinonims.twig' ), $context );
