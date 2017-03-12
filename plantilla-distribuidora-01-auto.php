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
        'orderby'       => 'menu_order',
        'order'         => 'asc',
        'posts_per_page'=> -1
    )
);

$context['links'] = array_map( function ( $post ) {
    $thumb_id = get_post_thumbnail_id( $post );
    if($thumb_id) {
        $thumb_url = wp_get_attachment_image_src( $thumb_id,'thumbnail-size', true );
        $image_url = $thumb_url[0];
    } else {
        $image_url = get_template_directory_uri() . '/static/images/content/generic_image_bloc.png';
    }


    return array(
        'link_url'         => get_permalink( $post ),
        'link_title'       => get_the_title( $post ),
        'link_image'       => $image_url,
        'link_description' => get_post_field('post_excerpt', $post)
    );
} , $all_children );

Timber::render( array( 'plantilla-distribuidora-01.twig' ), $context );
