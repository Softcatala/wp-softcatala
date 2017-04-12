<?php
/**
 * The Template for displaying single projecte page
 *
 * @package  wp-softcatala
 */

$context = Timber::get_context();
$post = Timber::query_post();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post'] = $post;

$logo = get_img_from_id( $post->logotip );
$context['logotip'] = $logo;

$yoastlogo = get_the_post_thumbnail_url() ?: $logo;

$custom_logo_filter = function ($img) use($yoastlogo) {
	return $yoastlogo;
};

add_filter( 'wpseo_twitter_image', $custom_logo_filter);
add_filter( 'wpseo_opengraph_image', $custom_logo_filter);

$context['links'] = $post->get_field( 'link' );
$context['credits'] = $post->get_field( 'credit' );

if ( is_array( $post->responsable ) ) {
    $context['responsables'] = get_users_metadata($post->responsable);
} else {
    $context['responsables'] = false;
}

//Contact Form Data
$context['contact']['to_email'] = 'web@softcatala.org';
$context['contact']['nom_from'] = 'Projectes de Softcatalà';
$context['contact']['assumpte'] = '[Projectes] Contacte des del formulari';

//Related subpages
$query = array ( 'post_id' => $post->ID, 'subpage_type' => 'projecte' );
$args = get_post_query_args( 'page', SearchQueryType::PagePrograma, $query );
query_posts($args);
$context['related_pages'] = Timber::get_posts($args);

if ( post_password_required( $post->ID ) ) {
    Timber::render( 'single-password.twig', $context );
} else {
    Timber::render( array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ), $context );
}
