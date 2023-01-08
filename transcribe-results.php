<?php
/**
 * Template Name: Transcribe-results
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-transcribe-results', get_template_directory_uri() . '/static/js/transcribe-results.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-otranscribe', get_template_directory_uri() . '/static/js/otranscribe.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-otranscribe-l10n', get_template_directory_uri() . '/static/js/otranscribe-l10n.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_style( 'sc-css-otranscribe', get_template_directory_uri() . '/static/css/otranscribe.css', array('sc-css-main'),WP_SOFTCATALA_VERSION );

$context = Timber::get_context();
$context['ads_container'] = true;
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');
$context['post'] = new TimberPost();
Timber::render( array( 'transcribe-results.twig' ), $context );

