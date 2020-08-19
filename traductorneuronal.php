<?php
/**
 * Template Name: Traductor Neuronal
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-clipboard', get_template_directory_uri() . '/static/js/clipboard.min.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-neuronal', get_template_directory_uri() . '/static/js/neuronal/main.js', array('sc-js-main', 'sc-js-metacookie'), WP_SOFTCATALA_VERSION, true );

$context = Timber::get_context();
//Ads
$context['ads_container'] = true;
$post = new TimberPost();
$context['post'] = $post;
$context['content_title'] = 'Traductor neuronal';

$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['to_email'] = get_option('email_traductor_neuronal');

Timber::render( array( 'traductorneuronal.twig' ), $context );
