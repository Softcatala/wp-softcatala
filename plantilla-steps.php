<?php
/**
 * Template Name: Plantilla Steps Softcatala
 *
 * @package wp-softcatala
 */
//JS and Styles related to the page
wp_enqueue_script( 'sc-js-steps', get_template_directory_uri() . '/static/js/steps.js', array(), '1.0.0', true );
wp_localize_script( 'sc-js-steps', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

//Template initialization
$templates = array('plantilla-steps.twig' );
$context = Timber::get_context();

//Project
$project_slug = get_query_var( 'project' );
if ( $project_slug != '' ) {
    $projecte = get_page_by_path( $project_slug , OBJECT, 'projecte' );
    $projecte = new TimberPost($projecte->ID);
    $content_title = 'Vull col·laborar en el projecte '. $projecte->post_title;
    $projecte->project_requirements = apply_filters('the_content', $projecte->project_requirements);
    $projecte->lectures_recomanades = apply_filters('the_content', $projecte->lectures_recomanades);
    $context['projecte'] = $projecte;
} else {
    $content_title = 'Vull col·laborar';
}

$post = new TimberPost();
$context['post'] = $post;
$context['content_title'] = $content_title;
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/ajudeu.twig', 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
Timber::render( $templates, $context );