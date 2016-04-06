<?php
/**
 * Template Name: Plantilla Steps Softcatala
 *
 * @package wp-softcatala
 */
//JS and Styles related to the page
wp_enqueue_script( 'sc-js-steps', get_template_directory_uri() . '/static/js/steps.js', array('sc-js-main'), '1.0.0', true );
wp_localize_script( 'sc-js-steps', 'scajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' )
));

//Template initialization
$templates = array('plantilla-steps.twig' );

//Project
$project_slug = get_query_var( 'project' );
if ( ! empty ( $project_slug ) ) {
    $projecte = get_page_by_path( $project_slug , OBJECT, 'projecte' );
    $projecte = new TimberPost($projecte->ID);
    $content_title = 'Col·laboreu en el projecte '. $projecte->post_title;
    $projecte->project_requirements = apply_filters('the_content', $projecte->project_requirements);
    $projecte->lectures_recomanades = apply_filters('the_content', $projecte->lectures_recomanades);

    $context_filterer = new SC_ContextFilterer();
    $context = $context_filterer->get_filtered_context( array('title' => $content_title . '| Softcatalà' ) );

    $context['projecte'] = $projecte;
    $context['steps'] = $projecte->get_field( 'steps' );
    $templates = array('plantilla-steps-single.twig' );

    $args = array(
        'meta_query' => array(
            get_meta_query_value('projectes', $projecte->ID, 'like', '')
        )
    );

    $context['membres'] = get_users( $args );
} else {
    $content_title = 'Vull col·laborar';
    $args = array(
        'post_type' => 'projecte',
        'meta_query' => array(
            array(
                'key' => 'wpcf-arxivat_pr',
                'value' => 0,
                'compare' => '='
            )
        )
    );

    $context_filterer = new SC_ContextFilterer();
    $context = $context_filterer->get_filtered_context( array('title' => $content_title . '| Softcatalà' ) );

    $projectes = Timber::get_posts($args);
    $context['projectes'] = $projectes;
    $context['post_lectures'] = $post = retrieve_page_data( 'lectures-recomanades' ); //looks for the page with slug lectures_recomanades-page
    $context['post_requirements'] = $post = retrieve_page_data( 'projectes-requeriments' ); //looks for the page with slug projecte_requeriments-page
}

$post = new TimberPost();
$context['post'] = $post;
$context['content_title'] = $content_title;
$context['links'] = $post->get_field( 'link' );
$context['sidebar_top'] = Timber::get_widgets('sidebar_top');
$context['sidebar_elements'] = array( 'static/dubte_forum.twig', 'baixades.twig', 'links.twig' );
$context['sidebar_bottom'] = Timber::get_widgets('sidebar_bottom');
Timber::render( $templates, $context );
