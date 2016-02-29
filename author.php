<?php
/**
 * The template for displaying Member pages
 *
 * @package  wp-softcatala
 */
//JS and Styles related to the page

//Template initialization
$data = Timber::get_context();
$data['posts'] = Timber::get_posts();
if ( isset( $wp_query->query_vars['author'] ) ) {
    $author = new TimberUser( $wp_query->query_vars['author'] );
    $data['author'] = $author;
    $data['author_role'] = get_user_role( $author );
    $data['author_content'] = apply_filters('the_content', $author->{'wpcf-descripcio_activitat'});
    $data['author_image'] = get_avatar( $author->ID, 270 );
    $data['title'] = 'Publicades per ' . $author->name();
}
Timber::render( array( 'single-author.twig', 'archive.twig' ), $data );



