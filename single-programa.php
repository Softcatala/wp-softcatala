<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  wp-softcatala
 */

wp_enqueue_script( 'sc-js-programes', get_template_directory_uri() . '/static/js/programes.js', array('sc-js-main'), '1.0.0', true );
wp_localize_script( 'sc-js-programes', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$context = Timber::get_context();
$post = Timber::query_post();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post'] = $post;
$context['comment_form'] = TimberHelper::get_comment_form();
$post_links = types_child_posts('link', $post->ID);
$context['links'] = $post->get_field( 'link' );
$context['baixades'] = $post->get_field( 'baixada' );
$context['credits'] = $post->get_field( 'credit' );

if ( post_password_required( $post->ID ) ) {
    Timber::render( 'single-password.twig', $context );
} else {
    Timber::render( array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context );
}
