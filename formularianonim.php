<?php
/**
 * Template Name: Formulari anònim
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array( 'jquery' ), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-contacte', 'scajax', array(
	'ajax_url' => admin_url( 'admin-ajax.php' )
));

$context = Timber::context();
$timberPost = Timber::get_post();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post'] = $timberPost;
$context['credits'] = $timberPost->meta( 'credits' );

//Contact Form Data
$context['contact']['to_email'] = get_option('email_denuncies');

Timber::render( array( 'formularianonim.twig' ), $context );
