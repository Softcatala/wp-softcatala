<?php
/**
 * Template Name: Subpàgina Programa
 *
 * @package wp-softcatala
 */
wp_enqueue_script( 'sc-js-pmf', get_template_directory_uri() . '/static/js/pmf.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_enqueue_script( 'sc-js-programes', get_template_directory_uri() . '/static/js/programes.js', array('sc-js-main'), WP_SOFTCATALA_VERSION, true );
wp_localize_script( 'sc-js-programes', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

$context = Timber::get_context();
$post_subpagina = new TimberPost();
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
$context['post_subpagina'] = $post_subpagina;
$post = new TimberPost( $post_subpagina->programa );
$context['post'] = $post;
$context['content_title'] = $post->title.' - PMF';
$query = array ( 'post_id' => $post_subpagina->programa, 'subpage_type' => 'programa' );
$args = get_post_query_args( 'page', SearchQueryType::PagePrograma, $query );
query_posts($args);
$context['related_pages'] = Timber::get_posts($args);

$baixades = $post->get_field( 'baixada' );
$context['baixades'] = generate_url_download( $baixades, $post );

Timber::render( array( 'subpagina-type.twig' ), $context );
