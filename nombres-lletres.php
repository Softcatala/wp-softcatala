<?php
/**
 * Template Name: Nombres en lletres
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-nombres-md5', get_template_directory_uri() . '/static/js/nombres-lletres/md5.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-nombres-soros', get_template_directory_uri() . '/static/js/nombres-lletres/Soros.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-nombres-ca-numbers', get_template_directory_uri() . '/static/js/nombres-lletres/ca-numbertext.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-nombres-roman', get_template_directory_uri() . '/static/js/nombres-lletres/roman-numbertext.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-nombres-sc-numbertext', get_template_directory_uri() . '/static/js/nombres-lletres/softcatala-numbertext.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_style( 'sc-css-nombres-lletres', get_template_directory_uri() . '/static/css/nombres-lletres.css', array('sc-css-main'),WP_SOFTCATALA_VERSION );

$context = Timber::get_context();
$context['ads_container'] = true;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');
$context['post'] = new TimberPost();
Timber::render( array( 'nombres-lletres.twig' ), $context );

