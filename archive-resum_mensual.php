<?php
/**
 * Archive page for the resum_mensual custom post type.
 *
 * Anonymous visitors never get here: sc_redirect_resum_mensual_to_login sends
 * them to the login page first.
 *
 * @package  wp-softcatala
 */

use Softcatala\Providers\ResumsMensuals;

$context_filterer  = new SC_ContextFilterer();
$context_overrides = array(
	'title'       => 'Resums mensuals - Softcatalà',
	'description' => 'Resums mensuals de l\'estat dels projectes de Softcatalà.',
);
$context = $context_filterer->get_filtered_context( $context_overrides, false );

global $wp_query;

$context['content_title'] = 'Resums mensuals';
$context['resums']        = array_map( array( ResumsMensuals::class, 'get_item' ), $wp_query->posts );
$context['pagination']    = Timber::get_pagination();

Timber::render( array( 'archive-resum_mensual.twig' ), $context );
