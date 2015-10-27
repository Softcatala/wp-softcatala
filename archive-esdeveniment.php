<?php

$templates = array( 'archive-esdeveniment.twig' );

$data = Timber::get_context();

///Get the related «page» to this post type (it will contain the links, downloads, actions...)
$args = array(
    'name'        => 'esdeveniments',
    'post_type'   => 'page'
);
$esdeveniments = get_posts($args);
$post = Timber::query_post($esdeveniments[0]->ID);
$data['post'] = $post;

$data['links'] = $post->get_field( 'link' );
$data['sidebar_top'] = Timber::get_widgets('sidebar_top');
$data['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$data['categories']['temes'] = Timber::get_terms('category', array('parent' => get_category_id('temes')));
$data['categories']['tipus'] = Timber::get_terms('category', array('parent' => get_category_id('tipus')));

//Retrieve posts
global $paged;
if (!isset($paged) || !$paged){
    $paged = 1;
}
$args = array(
    'post_type' => 'esdeveniment',
    'meta_key' => 'wpcf-data_inici',
    'orderby' => 'meta_value_num',
    'order' => 'ASC',
    'paged' => $paged
);
query_posts($args);
$data['posts'] = Timber::get_posts($args);
$data['pagination'] = Timber::get_pagination();

Timber::render( $templates, $data );
