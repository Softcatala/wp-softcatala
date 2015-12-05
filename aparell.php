<?php
/**
 * Template Name: Aparells
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$post = new TimberPost();
$parent_data = get_page_parent_title( $post );
$context['parent_title'] = $parent_data['title'];
$context['page_hierarchy'] = get_parent_page_hierarchy($parent_data['id'], 'DESC');
$context['post'] = $post;

//$object = get_option('wpcf-tipus', 'false');
$object = do_shortcode('[types field="wpcf-tipus"]');
var_dump($object);
die();


Timber::render( array( 'aparells.twig' ), $context );


