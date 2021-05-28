<?php
/**
 * Template Name: Distribuïdora automàtica amb capçalera/enllaços
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$timberPost = new TimberPost();
$context['post'] = $timberPost;

$all_children = get_posts(
    array(
        'post_type'     => 'page',
        'post_parent'   => $timberPost->ID,
        'orderby'       => 'menu_order',
        'order'         => 'asc',
        'posts_per_page'=> -1
    )
);

$context['links'] = array_map( function ( $timberPost ) {
    return array(
        'link_url'      => get_permalink( $timberPost ),
        'link_title'    => get_the_title( $timberPost )
    );
} , $all_children );

Timber::render( array( 'plantilla-distribuidora-02.twig' ), $context );