<?php
/**
 * Template Name: Planeta
 *
 * @package wp-softcatala
 */

global $xv_planeta;

$context = Timber::get_context();
$context['post'] = new TimberPost();

if ( isset( $xv_planeta) ) {
	$context['gent'] = $xv_planeta->get_user_list();
	$context['feed'] = $xv_planeta->get_feed();
} else {
	$context['gent'] = array();
	$context['feed'] = array();
}

Timber::render(array('planeta.twig'), $context);