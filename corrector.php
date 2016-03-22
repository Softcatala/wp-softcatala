<?php
/**
 * Template Name: Corrector Softcatala
 *
 * @package wp-softcatala
 */

/* JS scripts */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), '1.0.0', true );
wp_enqueue_script( 'sc-js-corrector-1', get_template_directory_uri() . '/inc/languagetool/online-check/tiny_mce/tiny_mce.js', array(), '1.0.0', true );
wp_enqueue_script( 'sc-js-corrector-2', get_template_directory_uri() . '/inc/languagetool/online-check/tiny_mce/plugins/atd-tinymce/editor_plugin.js', array(), '1.0.0', true );
wp_enqueue_script( 'sc-js-corrector-3', get_template_directory_uri() . '/inc/languagetool/js/ZeroClipboard.js', array(), '1.0.0', true );
wp_enqueue_script( 'sc-js-corrector-4', get_template_directory_uri() . '/inc/languagetool/js/jquery.metacookie.js', array(), '1.0.0', true );
wp_enqueue_script( 'sc-js-corrector-5', get_template_directory_uri() . '/static/js/languagetool.js', array(), '1.0.0', true );
wp_enqueue_script( 'sc-js-corrector-6', get_template_directory_uri() . '/static/js/jquery.fancybox-2.1.5.pack.js', array(), '1.0.0', true );
wp_enqueue_style( 'sc-css-corrector', get_template_directory_uri() . '/static/css/languagetool.css', array(), '1.0' );
wp_localize_script( 'sc-js-corrector-1', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
$context['content_title'] = 'Corrector';
$context['links'] = $post->get_field( 'link' );
$context['credits'] = $post->get_field( 'credit' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['to_email'] = get_option('email_recursos');

Timber::render( array( 'corrector.twig' ), $context );