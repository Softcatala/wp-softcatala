<?php
/**
 * Template Name: Plantilla Text Menu Left
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$post = new TimberPost();
$parent_data = get_page_parent_title( $post );
$context['parent_title'] = $parent_data['title'];
$context['page_hierarchy'] = get_parent_page_hierarchy($parent_data['id']);
$context['post'] = $post;
$context['links'] = $post->get_field( 'link' );
Timber::render( array( 'plantilla-text-menuleft.twig' ), $context );


function get_page_parent_title( $post ) {
    $parent = array_reverse( get_post_ancestors( $post->ID ) );
    $parent_data['id'] = $parent[0];
    $parent_data['title'] = get_the_title( $parent[0] );
    return $parent_data;
}

function get_parent_page_hierarchy($parent_id, $limit = -1) {
    $pages_tree = wp_list_pages( array(
        'child_of' => $parent_id,
        'echo' => 0,
        'link_before' => '<i class="fa fa-angle-right"></i>',
        'title_li' => '',
    ) );

    $pages_tree = str_replace( 'children', 'nav children', $pages_tree);

    return $pages_tree;
}

