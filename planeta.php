<?php
/**
 * Template Name: Planeta
 *
 * @package wp-softcatala
 */

function xv_title_sorter($a, $b) {
	return strcasecmp($a['title'], $b['title']);
}

global $xv_planeta;

if ( isset( $xv_planeta) ) {
	$xv_planeta->register_rss_link();
	$gent = $xv_planeta->get_user_list();
	$feed = $xv_planeta->get_feed();
} else {
	$gent = array();
	$feed = array();
}

$context = Timber::context();
$context['post'] = Timber::get_post();

usort($gent, 'xv_title_sorter');

$context['gent'] = $gent;
$context['feed'] = $feed;

Timber::render(array('planeta.twig'), $context);