<?php
/**
 * Template Name: Plantilla Membres
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page


//Template initialization
$title = 'Membres - Softcatalà';
$description = 'Membres, col·laboradors, gent de Softcatalà';

if ( ! empty ( $wp_query->query_vars['author'] ) ) {
    $template = array( 'single-author.twig' );
    $author = new TimberUser( $wp_query->query_vars['author'] );
    $context_holder['author'] = $author;
    $context_holder['author_role'] = get_user_role( $author );
    $context_holder['author_content'] = apply_filters('the_content', $author->{'wpcf-descripcio_activitat'});
    $context_holder['author_image'] = get_avatar( $author->ID, 270 );
    $context_holder['content_title'] = 'Publicades per ' . $author->name();
    $title = $author->name() . ' - Softcatalà';
    $description = $author->description();

    $projectes_ids = get_user_meta($author->ID, 'projectes', true);

    if($projectes_ids) {
        $context_holder['projectes'] = array_map( function ($projecte_id) {
            $_projecte = get_post($projecte_id);
            return array(
                'link' => get_post_permalink($projecte_id),
                'title' => $_projecte->post_title,
            );
        }, $projectes_ids );
    }
} else {
    $template = array( 'archive-author.twig' );
    $post = new TimberPost();
    $context_holder['post'] = $post;
    //Show only active members
    $args = array(
        'meta_query' => array(
            get_meta_query_value('wpcf-status_membre', 0, '>', '')
        )
    );
    $authors = get_users($args);
    $context_holder['authors'] = $authors;
    $context_holder['content_title'] = 'Membres de Softcatalà';
    $context_holder['sidebar_elements'] = array( 'static/suggeriment.twig', 'baixades.twig', 'links.twig' );
}

//Context initialization
$context_filterer = new SC_ContextFilterer( $context_holder );
$context_overrides = array( 'title' => $title, 'description' => $description );
$context = $context_filterer->get_filtered_context( $context_overrides, false );

Timber::render( $template, $context_holder );