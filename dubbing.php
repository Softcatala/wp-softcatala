<?php
/**
 * Template Name: Dubbing
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-dubbing', get_template_directory_uri() . '/static/js/dubbing.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_style( 'sc-css-dubbing', get_template_directory_uri() . '/static/css/transcribe.css', array('sc-css-main'),WP_SOFTCATALA_VERSION );

$context = Timber::get_context();
$context['ads_container'] = true;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');
$context['post'] = new TimberPost();
Timber::render( array( 'dubbing.twig' ), $context );

