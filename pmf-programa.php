<?php
/**
 * Template Name: PMF Programes
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-pmf', get_template_directory_uri() . '/static/js/pmf.js', array('sc-js-main'), '1.0.0', true );

$context = Timber::get_context();
$post = new TimberPost();
$context['post_pmf'] = $post;
$context['post'] = new TimberPost( $post->postid );
$context['content_title'] = 'PMF';
Timber::render( array( 'pmf-programa.twig' ), $context );