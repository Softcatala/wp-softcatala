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

Timber::render( array( 'memories.twig' ), $context );