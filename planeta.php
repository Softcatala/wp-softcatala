<?php
/**
 * Template Name: Planeta
 *
 * @package wp-softcatala
 */

global $xv_planeta;

$context = Timber::get_context();
$context['post'] = new TimberPost();
$context['gent'] = $xv_planeta->get_user_list();
$context['planeta_feed'] = $xv_planeta->get_feed();

Timber::render(array('planeta.twig'), $context);