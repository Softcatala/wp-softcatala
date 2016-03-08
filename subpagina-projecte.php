<?php
/**
 * Template Name: Subpàgina Projecte
 *
 * @package wp-softcatala
 */

$context = Timber::get_context();
$post_subpagina = new TimberPost();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post_subpagina'] = $post_subpagina;
$post = new TimberPost( $post_subpagina->projecte );
$context['post'] = $post;
$query = array ( 'post_id' => $post_subpagina->projecte, 'subpage_type' => 'projecte' );

//Related subpages
$args = get_post_query_args( 'page', SearchQueryType::PagePrograma, $query );
query_posts($args);
$context['related_pages'] = Timber::get_posts($args);

Timber::render( array( 'subpagina-type.twig' ), $context );