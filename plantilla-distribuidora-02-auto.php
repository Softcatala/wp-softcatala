<?php
/**
 * Template Name: Distribuïdora automàtica amb capçalera/enllaços
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;

$all_children = get_posts(
    array(
        'post_type'     => 'page',
        'post_parent'   => $post->ID,
        'orderby'       => 'menu_order',
        'order'         => 'asc'
    )
);

$context['links'] = array_map( function ( $post ) {
    return array(
        'link_url'      => get_permalink( $post ),
        'link_title'    => get_the_title( $post ) 
    );
} , $all_children );

Timber::render( array( 'plantilla-distribuidora-02.twig' ), $context );