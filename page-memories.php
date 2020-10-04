<?php
/**
 * Template Name: Memòries de traducció
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;

$sc_memory = new SC_Memory();
$generation_data = $sc_memory->get_generation_data();
$context['projects'] = $generation_data->memories;
$context['last_generation'] = $generation_data->generation_date;

/* JS scripts */
wp_enqueue_script( 'sc-js-chosen', get_template_directory_uri() . '/static/js/memories/chosen.jquery.min.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-memories', get_template_directory_uri() . '/static/js/memories/memories.js', array('sc-js-chosen'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_style( 'sc-css-chosen', get_template_directory_uri() . '/static/css/memories/chosen.css', array(), WP_SOFTCATALA_VERSION );

Timber::render( array( 'memories.twig' ), $context );