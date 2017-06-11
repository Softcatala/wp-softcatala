<?php
/**
 * Template Name: Traductor Softcatala
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-traductor', get_template_directory_uri() . '/static/js/traductor.js', array('sc-js-main', 'sc-js-metacookie'), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-traductor', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$context = Timber::get_context();
//Ads
$context['ads_container'] = true;
$post = new TimberPost();
$context['post'] = $post;
$context['content_title'] = 'Traductor';
$context['credits'] = $post->get_field( 'credits' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['to_email'] = get_option('email_traductor');

Timber::render( array( 'traductor.twig' ), $context );
    

