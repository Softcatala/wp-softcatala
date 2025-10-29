<?php
/**
 * Archive page for esdeveniment custom post type
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page
wp_enqueue_script( 'sc-js-esdeveniments', get_template_directory_uri() . '/static/js/esdeveniments.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-novetats', get_template_directory_uri() . '/static/js/novetats.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-novetats', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

//Template initialization
$templates = array('archive-esdeveniment.twig' );

$title = 'Esdeveniments - Softcatalà';
$description = 'Esdeveniments relacionats amb el món de la tecnologia i el català.';
$post = Timber::get_post();

//Context initialization
$context_filterer = new SC_ContextFilterer();
$context_overrides = array( 'title' => $title, 'description' => $description );
$context = $context_filterer->get_filtered_context( $context_overrides, false );
$context['content_title'] = 'Esdeveniments';
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['posts'] = Softcatala\Providers\Esdeveniments::get();
$context['post'] = $post;
$context['share_title'] = $context['content_title'];

Timber::render( $templates, $context );
