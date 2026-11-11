<?php
/**
 * Single monthly summary.
 *
 * Anonymous visitors never get here: sc_redirect_resum_mensual_to_login sends
 * them to the login page first.
 *
 * @package  wp-softcatala
 */

use Softcatala\Providers\ResumsMensuals;

$context = Timber::context();
$post    = Timber::get_post();

$context['post']     = $post;
$context['resum']    = ResumsMensuals::get_item( $post );
$context['adjacent'] = ResumsMensuals::get_adjacent( $post->ID );
$context['recents']  = ResumsMensuals::get_recent( 6 );
$context['toc']      = ResumsMensuals::get_toc( $post->post_content );

Timber::render( array( 'single-resum_mensual.twig' ), $context );
