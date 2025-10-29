<?php
/**
 * Template Name: Home Softcatala
 *
 * @package wp-softcatala
 */

//Template initialization
$templates = array('home-sc.twig' );
$context = Timber::context();
$context['ads_container'] = true;

//Sections population
$context['slides'] = Timber::get_posts( array( 'post_type' => 'slider' ) );
$context['posts'] = Timber::get_posts(array( 'post_type' => 'post', 'posts_per_page' => '3', 'post_status' => 'publish' ));
$context['programari'] = get_top_downloads_home();
$context['podcasts'] = Timber::get_posts( array( 'post_type' => 'podcast', 'posts_per_page' => 1 ) );
$context['recursos'] = get_field('recursos');

Timber::render( $templates, $context );
