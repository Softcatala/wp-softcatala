<?php
/**
 * Template Name: Subpàgina Projecte
 *
 * @package wp-softcatala
 */


$post_subpagina = Timber::get_post();

$timberPost = Timber::get_post( $post_subpagina->projecte );

$context_filter = new SC_ContextFilterer();

$context = $context_filter->get_filtered_context( array ( 'prefix_title' => $timberPost->title ) );

$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post_subpagina'] = $post_subpagina;
$context['post'] = $timberPost;
$context['current_url'] = get_current_url();

$logo = get_img_from_id( $timberPost->logotip );

$context['logotip'] = $logo;

$custom_logo_filter = function ($img) use($logo ) {
	return $logo;
};

add_filter( 'wpseo_twitter_image', $custom_logo_filter);
add_filter( 'wpseo_opengraph_image', $custom_logo_filter);

$context['credits'] = $timberPost->meta( 'credits' );

if ( is_array( $timberPost->responsable ) ) {
    $context['responsables'] = get_users_metadata($timberPost->responsable);
} else {
    $context['responsables'] = false;
}

//Contact Form Data
$context['contact']['to_email'] = 'web@softcatala.org';
$context['contact']['nom_from'] = 'Projectes de Softcatalà';
$context['contact']['assumpte'] = '[Projectes] Contacte des del formulari';

$query = array ( 'post_id' => $post_subpagina->projecte, 'subpage_type' => 'projecte' );

//Related subpages
$args = get_post_query_args( 'page', SearchQueryType::PageProjecte, $query );
query_posts($args);
$context['related_pages'] = Timber::get_posts($args);

Timber::render( array( 'subpagina-type.twig' ), $context );
