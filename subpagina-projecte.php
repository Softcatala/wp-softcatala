<?php
/**
 * Template Name: Subpàgina Projecte
 *
 * @package wp-softcatala
 */


$post_subpagina = new TimberPost();

$post = new TimberPost( $post_subpagina->projecte );

$context_filter = new SC_ContextFilterer();

$context = $context_filter->get_filtered_context( array ( 'prefix_title' => $post->title ) );

$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post_subpagina'] = $post_subpagina;
$context['post'] = $post;
$context['current_url'] = get_current_url();

$query = array ( 'post_id' => $post_subpagina->projecte, 'subpage_type' => 'projecte' );

//Related subpages
$args = get_post_query_args( 'page', SearchQueryType::PagePrograma, $query );
query_posts($args);
$context['related_pages'] = Timber::get_posts($args);

Timber::render( array( 'subpagina-type.twig' ), $context );
