<?php
/**
 * Template Name: Plantilla Membres
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page


//Template initialization
$data = Timber::get_context();

if ( ! empty ( $wp_query->query_vars['author'] ) ) {
    $template = array( 'single-author.twig' );
    $author = new TimberUser( $wp_query->query_vars['author'] );
    $data['author'] = $author;
    $data['author_role'] = get_user_role( $author );
    $data['author_content'] = apply_filters('the_content', $author->{'wpcf-descripcio_activitat'});
    $data['author_image'] = get_avatar( $author->ID, 270 );
    $data['content_title'] = 'Publicades per ' . $author->name();
} else {
    $template = array( 'archive-author.twig' );
    $post = new TimberPost();
    $data['post'] = $post;
    //Show only active members
    $args = array(
        'meta_query' => array(
            get_meta_query_value('wpcf-status_membre', 0, '>', '')
        )
    );
    $authors = get_users($args);
    $data['authors'] = $authors;
    $data['content_title'] = 'Membres de Softcatalà';
    $data['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );
}

Timber::render( $template, $data );