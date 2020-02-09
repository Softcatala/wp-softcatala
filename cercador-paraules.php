<?php
/**
 * Template Name: Cercador avançat de paraules
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-sillabesca', get_template_directory_uri() . '/static/js/sillabes-ca.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-hyphen', get_template_directory_uri() . '/static/js/hyphen.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-hyphen-softcatala', get_template_directory_uri() . '/static/js/hyphen-softcatala.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );


$context = Timber::get_context();
$context['ads_container'] = true;
$post = new TimberPost();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');
$context['post'] = $post;
Timber::render( array( 'cercador-paraules.twig' ), $context );

