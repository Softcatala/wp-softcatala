<?php
/**
 * Template Name: Distribuïdora automàtica amb blocs verticals
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
        'orderby'       => 'menu_order'
    )
);

$context['links'] = array_map( function ( $post ) {
    $thumb_id = get_post_thumbnail_id( $post );
    $thumb_url = wp_get_attachment_image_src( $thumb_id,'thumbnail-size', true );

    return array(
        'link_url'         => get_permalink( $post ),
        'link_title'       => get_the_title( $post ),
        'link_image'       => $thumb_url[0],
        'link_description' => get_post_field('post_excerpt', $post)
    );
} , $all_children );

Timber::render( array( 'plantilla-distribuidora-01.twig' ), $context );