<?php
/**
 * Template Name: Corrector Softcatala
 *
 * @package wp-softcatala
 */

/* JS scripts */
wp_enqueue_script( 'sc-js-corrector-1', get_template_directory_uri() . '/inc/languagetool/online-check/tiny_mce/tiny_mce.js', array(), '1.0.0', true );
wp_enqueue_script( 'sc-js-corrector-2', get_template_directory_uri() . '/inc/languagetool/online-check/tiny_mce/plugins/atd-tinymce/editor_plugin.js', array(), '1.0.0', true );
wp_enqueue_script( 'sc-js-corrector-3', get_template_directory_uri() . '/inc/languagetool/js/ZeroClipboard.js', array(), '1.0.0', true );
wp_enqueue_script( 'sc-js-corrector-4', get_template_directory_uri() . '/inc/languagetool/js/jquery.metacookie.js', array(), '1.0.0', true );
wp_enqueue_script( 'sc-js-corrector-5', get_template_directory_uri() . '/static/js/languagetool.js', array(), '1.0.0', true );
wp_enqueue_script( 'sc-js-corrector-6', get_template_directory_uri() . '/static/js/jquery.fancybox-2.1.5.pack.js', array(), '1.0.0', true );
wp_enqueue_style( 'sc-css-corrector', get_template_directory_uri() . '/static/css/languagetool.css', array(), '1.0' );


$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
Timber::render( array( 'corrector.twig' ), $context );