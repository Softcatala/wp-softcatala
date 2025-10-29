<?php
/**
 * Template Name: Subpàgina Programa
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'jquery-browser-plugin', get_template_directory_uri() . '/static/js/jquery.browser.min.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-programes', get_template_directory_uri() . '/static/js/programes.js', array('sc-js-main', 'jquery-browser-plugin'), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-programes', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$post_subpagina = Timber::get_post();
$post = Timber::get_post( $post_subpagina->programa );

$context = get_program_context( $post );

$context['post_subpagina'] = $post_subpagina;

Timber::render( array( 'subpagina-type.twig' ), $context );
