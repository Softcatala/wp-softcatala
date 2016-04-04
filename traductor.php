<?php
/**
 * Template Name: Traductor Softcatala
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-contacte', get_template_directory_uri() . '/static/js/contact_form.js', array('sc-js-main'), '1.0.0', true );
wp_enqueue_script( 'sc-js-traductor', get_template_directory_uri() . '/static/js/traductor.js', array('sc-js-main'), '1.0.0', true );
wp_localize_script( 'sc-js-traductor', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$context = Timber::get_context();
//Ads
$context['ads_container'] = generate_ads_html( array( '13', '17' ));
$post = new TimberPost();
$context['post'] = $post;
$context['content_title'] = 'Traductor';
$context['links'] = $post->get_field( 'link' );
$context['credits'] = $post->get_field( 'credit' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top_recursos');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom_recursos');

//Contact Form
$context['contact']['to_email'] = get_option('email_traductor');

Timber::render( array( 'traductor.twig' ), $context );
    

