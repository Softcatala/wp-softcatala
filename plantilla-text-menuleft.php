<?php
/**
 * Template Name: Plantilla text amb menú esquerra
 *
 * @package wp-softcatala
 */

$timberPost = new \Timber\Post();

if( $timberPost->pmf ) {
	wp_enqueue_script(
		'sc-js-pmf',
		get_template_directory_uri() . '/static/js/pmf.js',
		array('sc-js-main'),
		WP_SOFTCATALA_VERSION,
		true
	);
}

$parent_data = get_page_parent_title( $timberPost->ID );

$context = \Timber\Timber::get_context();
$context['content_title'] = $parent_data['title'];
$context['page_hierarchy'] = wp_list_subpages($parent_data['id']);
$context['post'] = $timberPost;
$context['breadcrumbs'] = get_breadcrumbs( $timberPost );


\Timber\Timber::render( array( 'plantilla-text-menuleft.twig' ), $context );

