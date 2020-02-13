<?php
/**
 * Template Name: L’hora en català
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-hora', get_template_directory_uri() . '/static/js/hora/hora.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-hora-md5', get_template_directory_uri() . '/static/js/hora/md5.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-hora-rellotge', get_template_directory_uri() . '/static/js/hora/rellotge.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-hora-start', get_template_directory_uri() . '/static/js/hora/start.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );

wp_enqueue_style( 'sc-css-hora', get_template_directory_uri() . '/static/css/hora.css', array('sc-css-main'),WP_SOFTCATALA_VERSION, all );

$context = Timber::get_context();
$context['ads_container'] = true;
$post = new TimberPost();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');
$context['post'] = $post;
Timber::render( array( 'hora-catala.twig' ), $context );

