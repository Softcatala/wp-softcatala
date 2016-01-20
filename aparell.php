<?php
/**
 * Template Name: Aparells
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-aparells', get_template_directory_uri() . '/static/js/aparells.js', array('sc-js-main'), '1.0.0', true );
wp_localize_script( 'sc-js-aparells', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$context = Timber::get_context();

$search = get_query_var('cerca');
$sistema_operatiu = get_query_var( 'sistema_operatiu' );
$tipus_aparell = get_query_var( 'tipus_aparell' );
if( ! empty( $search ) || ! empty( $sistema_operatiu ) || ! empty( $tipus_aparell ) ) {
    $query_aparell['s'] = $search;
    $query_aparell['sistema_operatiu_aparell'] = $sistema_operatiu;
    $query_aparell['tipus_aparell'] = $tipus_aparell;
    $args = get_post_query_args( 'aparell', SearchQueryType::Aparell, $query_aparell );
    $context['cerca'] = $search;
    $context['selected_filter_so'] = ( isset ( $args['filter_so'] ) ? $args['filter_so'] : '' );
    $context['selected_filter_tipus'] = ( isset ( $args['filter_tipus'] ) ? $args['filter_tipus'] : '' );
} else {
    $args = array( 'post_type' => 'aparell' );
}

$post = new TimberPost();
$parent_data = get_page_parent_title( $post );
$context['categories']['sistemesoperatius'] = Timber::get_terms('sistema_operatiu_aparell');
$context['categories']['tipus'] = Timber::get_terms('tipus_aparell');
$context['parent_title'] = $parent_data['title'];
$context['page_hierarchy'] = get_parent_page_hierarchy($parent_data['id'], 'DESC');
query_posts($args);
$context['post'] = $post;
$context['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );
$context['links'] = $post->get_field( 'link' );
$context['aparells'] = Timber::get_posts($args);
Timber::render( array( 'aparells.twig' ), $context );


function ajax_action_stuff() {
    echo 'ajax submitted';
    die(); // stop executing script
}